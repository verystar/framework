<?php namespace Very\Support;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 fifsky@gmail.com
 * Date: 14-6-24
 * Time: 下午2:38
 *
 * 统计插件，必须安装redis，本功能为了确保可以独立运行，因此配置和DB操作都独立包含进来
 */

use Redis;
use RedisException;
use Very\Database\Connection;

class Stat
{
    public $use_db  = 'stat';

    public $redis;
    public $redis_config = [];

    //暂存数据
    public $data = [];

    public function __construct($config)
    {
        $this->is_stat = $config['is_stat'];

        if ($this->is_stat) {
            $this->use_db       = $config['use_db'];
            $this->db_prefix    = $config['db_prefix'];
            $this->redis_config = $config['redis_config'];
            $this->connect();
        }
    }

    private function connect()
    {
        try {
            $this->redis  = new Redis();
            $redis_config = $this->redis_config;
            $this->redis->connect($redis_config['host'], $redis_config['port'], $redis_config['timeout']);
        } catch (RedisException $e) {
            logger()->error('Fstat redis connect error', ["msg" => $e->getMessage(), "file" => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    /**
     * @param      $num
     * @param      $v1      //统计主标题
     * @param      $v2      //统计此标题
     * @param      $v3      //统计项
     * @param null $v4      //采样信息
     * @param int  $v5      //采样率，默认10%，如果需要千分之一则0.1
     * @param bool $replace //是否覆盖num
     *
     * @return bool
     */
    public function set($num, $v1, $v2, $v3, $v4 = null, $v5 = 10, $replace = false)
    {
        if (!$this->is_stat) {
            return false;
        }

        $num = (int)$num;
        $v1  = trim($v1);
        $v2  = trim($v2);
        $v3  = trim($v3);
        $v4  = trim($v4);
        if ($num <= 0 || !isset($v1[0]) || !isset($v2[0]) || !isset($v3[0])) {
            return false;
        }

        //采样
        if ($v4 !== null) {
            $prob = ceil(100 / $v5);
            $rt   = mt_rand(1, $prob);
            $v4   = $rt == 1 ? substr($v4, 0, 500) : null;
        }

        $data = array(
            //按照DB划分
            'dbf'     => $this->db_prefix,
            //累计值
            'num'     => (int)$num,
            //大分类
            'v1'      => $v1,
            //小分类
            'v2'      => $v2,
            //具体统计对象
            'v3'      => $v3,
            //采样信息
            'v4'      => $v4,
            'replace' => $replace,
            //记录时间
            'time'    => time(),
        );
        array_push($this->data, $data);

        if (count($this->data) >= 100) {
            $this->log();
            $this->data = array();
        }
    }

    private function isConnectionLost(RedisException $e)
    {
        if (strpos($e->getMessage(), 'Redis server went away') !== false || strpos($e->getMessage(), 'Connection lost') !== false || strpos($e->getMessage(),
                'read error on connection') !== false
        ) {
            return true;
        }
        return false;
    }

    public function lpushLog($data)
    {
        for ($i = 0; $i < 2; $i++) {
            try {
                $data = json_encode($data);
                $this->redis->lPush('__stat__', $data);
            } catch (RedisException $e) {
                //如果redis断开了连接则需要重新连接执行
                if ($this->isConnectionLost($e)) {
                    $this->connect();
                    continue;
                }
            }
            break;
        }
    }

    //插入redis队列
    private function log()
    {

        if (!$this->is_stat) {
            return false;
        }

        for ($i = 0; $i < 2; $i++) {

            try {
                $redis = $this->redis->multi(Redis::PIPELINE);

                foreach ($this->data as $data) {
                    $data = json_encode($data);
                    $redis->rPush('__stat__', $data);
                }
                $redis->exec();
                $this->data = [];
            } catch (RedisException $e) {
                logger()->error('Fstat redis exec error', ["msg" => $e->getMessage(), "file" => $e->getFile(), 'line' => $e->getLine()]);
                //如果redis断开了连接则需要重新连接执行
                if ($this->isConnectionLost($e)) {
                    $this->connect();
                    continue;
                }
            }

            break;
        }
    }

    public function formatTime($diff_time = 0)
    {
        if ($diff_time < 0.01) {
            $diff_time_str = "0.00s到0.01s";
        } elseif ($diff_time < 0.02) {
            $diff_time_str = "0.01s到0.02s";
        } elseif ($diff_time < 0.03) {
            $diff_time_str = "0.02s到0.03s";
        } elseif ($diff_time < 0.04) {
            $diff_time_str = "0.03s到0.04s";
        } elseif ($diff_time < 0.05) {
            $diff_time_str = "0.04s到0.05s";
        } elseif ($diff_time < 0.1) {
            $diff_time_str = "0.05s到0.1s";
        } elseif ($diff_time < 0.5) {
            $diff_time_str = "0.1s到0.5s";
        } elseif ($diff_time < 1) {
            $diff_time_str = "0.5s到1s";
        } elseif ($diff_time < 5) {
            $diff_time_str = "1s到5s";
        } elseif ($diff_time < 10) {
            $diff_time_str = "5s到10s";
        } else {
            $diff_time_str = "10s到∞秒";
        }
        return $diff_time_str;
    }

    public function diffTime($start_time, $end_time = null)
    {
        if ($end_time === null) {
            $end_time = microtime(true);
        }
        $diff_time = sprintf('%.5f', $end_time - $start_time);
        return $this->formatTime($diff_time);
    }

    /*
     * return MysqlPDO
     */
    public function db()
    {
        return Connection::getInstance($this->use_db);
    }

    public function isExist($md5, $type, $db_prefix)
    {
        if ($type == 'v4') {
            $sql = "select count(*) from {$db_prefix}stat where md5 = :md5 limit 1";
        } elseif ($type == 'v3') {
            $sql = "select count(*) from {$db_prefix}stat_hour where md5 = :md5 limit 1";
        } elseif ($type == 'v2') {
            $sql = "select count(*) from {$db_prefix}stat_day where md5 = :md5 limit 1";
        }
        return $this->db()->getOne($sql, array('md5' => $md5));
    }

    //保存到数据库
    public function statSave($num = 100, $projects = [])
    {
        $redis = $this->redis->multi(Redis::PIPELINE);
        for ($i = 0; $i < $num; $i++) {
            $redis->lPop('__stat__');
        }
        $datas    = $redis->exec();
        $stat_arr = array();

        foreach ($datas as $v) {
            if ($v !== false) {
                $v         = json_decode($v, true);
                $time      = $v['time'];
                $v['time'] = date('Y-m-d H', $v['time']);
                $md5       = md5($v['v1'] . $v['v2'] . $v['v3'] . $v['v4'] . $v['time']);

                //先合并相同的统计
                if (isset($stat_arr[$v['dbf']][$md5])) {
                    //是否覆盖
                    if ($v['replace']) {
                        $stat_arr[$v['dbf']][$md5]['num'] = $v['num'];
                    } else {
                        $stat_arr[$v['dbf']][$md5]['num'] += $v['num'];
                    }
                } else {
                    $stat_arr[$v['dbf']][$md5]           = $v;
                    $stat_arr[$v['dbf']][$md5]['md5_v2'] = md5($v['v1'] . $v['v2'] . date('Y-m-d', $time));
                    $stat_arr[$v['dbf']][$md5]['md5_v3'] = md5($v['v1'] . $v['v2'] . $v['v3'] . $v['time']);
                }
            }
        }

        $sql_plus    = "update %sstat set num = num + :num where md5 = :md5";
        $sql_replace = "update %sstat set num = :num where md5 = :md5";
        $sql2        = "insert into %sstat(num,v1,v2,v3,v4,md5,md5_v2,md5_v3,add_time) values (:num,:v1,:v2,:v3,:v4,:md5,:md5_v2,:md5_v3,:add_time)";
        $stat_dbfs   = [];
        foreach ($projects as $v) {
            $stat_dbfs[$v['db_prefix']] = 1;
        }
        foreach ($stat_arr as $dbf => $stats) {
            if (!isset($stat_dbfs[$dbf])) {
                //如果不是监控系统分配的db_prefix则不处理
                $this->set(1, 'STAT错误', "DB不存在", $dbf);
                continue;
            }

            foreach ($stats as $k => $v) {
                if ($this->isExist($k, 'v4', $dbf)) {
                    if ($v['replace']) {
                        $sql1 = $sql_replace;
                    } else {
                        $sql1 = $sql_plus;
                    }

                    $this->db()->execute(sprintf($sql1, $dbf), array('num' => $v['num'], 'md5' => $k));
                } else {
                    $this->db()->execute(sprintf($sql2, $dbf), array(
                        'num'      => $v['num'],
                        'v1'       => $v['v1'],
                        'v2'       => $v['v2'],
                        'v3'       => $v['v3'],
                        'v4'       => $v['v4'],
                        'md5'      => $k,
                        'md5_v2'   => $v['md5_v2'],
                        'md5_v3'   => $v['md5_v3'],
                        'add_time' => $v['time']
                    ));
                }
            }
        }
    }

    /**
     * 数据汇总，每小时汇总一次小时数据
     */
    public function statSumHour($db_prefix, $add_time = null)
    {
        $sql = "select sum(num) as num,v1,v2,v3,md5_v3 from {$db_prefix}stat where add_time = :add_time group by md5_v3";
        if ($add_time === null) {
            $add_time = date('Y-m-d H', strtotime('-1 hour'));
        }
        $hour_arr = $this->db()->getAll($sql, array('add_time' => $add_time));

        $sql1 = "update {$db_prefix}stat_hour set num = :num where md5 = :md5";
        $sql2 = "insert into {$db_prefix}stat_hour(num,v1,v2,v3,md5,add_time) values (:num,:v1,:v2,:v3,:md5,:add_time)";
        foreach ($hour_arr as $v) {

            if ($this->isExist($v['md5_v3'], 'v3', $db_prefix)) {
                $this->db()->execute($sql1, array('num' => $v['num'], 'md5' => $v['md5_v3']));
            } else {
                $this->db()->execute($sql2,
                    array('num' => $v['num'], 'v1' => $v['v1'], 'v2' => $v['v2'], 'v3' => $v['v3'], 'md5' => $v['md5_v3'], 'add_time' => $add_time));
            }
        }

    }


    /**
     * 数据汇总，每小时汇总一次当天数据
     */
    public function statSumDay($db_prefix, $add_time = null)
    {
        if ($add_time === null) {
            $add_time = date('Y-m-d');
        }

        //此处如果使用date_format则不会走索引，因此调整为between，效率提升30倍
        $start_time = $add_time . ' 00:00:00';
        $end_time   = $add_time . ' 23:59:59';

        $sql      = "select sum(num) as num,v1,v2,md5_v2 from {$db_prefix}stat where add_time BETWEEN :start_time and :end_time group by md5_v2";
        $hour_arr = $this->db()->getAll($sql, array('start_time' => $start_time, 'end_time' => $end_time));

        $sql1   = "update {$db_prefix}stat_day set num = :num where md5 = :md5";
        $sql2   = "insert into {$db_prefix}stat_day(num,v1,v2,md5,add_time) values (:num,:v1,:v2,:md5,:add_time)";
        $v1_arr = array();
        foreach ($hour_arr as $v) {

            if ($this->isExist($v['md5_v2'], 'v2', $db_prefix)) {
                $this->db()->execute($sql1, array('num' => $v['num'], 'md5' => $v['md5_v2']));
            } else {
                $this->db()->execute($sql2, array('num' => $v['num'], 'v1' => $v['v1'], 'v2' => $v['v2'], 'md5' => $v['md5_v2'], 'add_time' => $add_time));
                $this->saveStatV1V2($v['v1'], $v['v2'], $db_prefix);
                if (!isset($v1_arr[$v['v1']])) {
                    $this->setStatGroup($v['v1'], $db_prefix);
                    $v1_arr[$v['v1']] = null;
                }
            }

        }

    }

    /**
     * 给v1分类
     *
     * @param $v1
     */
    public function setStatGroup($v1, $db_prefix)
    {
        $sql      = "select count(*) from {$db_prefix}stat_group where v1 = :v1";
        $is_exist = $this->db()->getOne($sql, array('v1' => $v1));
        if (!$is_exist) {
            $sql = "insert into {$db_prefix}stat_group(v1,group_name1,group_name2,group_name3) values (:v1,'默认','默认','默认')";

            $this->db()->execute($sql, array('v1' => $v1));
        }
    }

    public function saveStatV1V2($v1, $v2, $db_prefix)
    {
        $sql      = "select count(*) from {$db_prefix}stat_vv where v1 = :v1 and v2 = :v2";
        $is_exist = $this->db()->getOne($sql, array('v1' => $v1, 'v2' => $v2));
        if (!$is_exist) {
            $sql = "insert into {$db_prefix}stat_vv(v1,v2) values (:v1,:v2)";
            $this->db()->execute($sql, array('v1' => $v1, 'v2' => $v2));
        }

    }

    public function __destruct()
    {
        $this->log();
    }
}