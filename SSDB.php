<?php namespace Very\Cache;

/**
 * Created by JetBrains PhpStorm.
 * User: CAIXUDONG
 * Date: 13-1-22
 * Time: 下午5:25
 */

use Very\Library\FStat;

class SSDB {

    //https://github.com/jonnywang/phpssdb/wiki
    //注意lpop rpop属于写操作
    private $write_methods = array(
        'set' => 1, 'setx' => 1, 'setnx' => 1, 'expire' => 1, 'ttl' => 1, 'getset' => 1, 'del' => 1, 'incr' => 1, 'setbit' => 1, 'multi_set' => 1, 'multi_del' => 1, 'hset' => 1, 'hdel' => 1, 'hincr' => 1, 'hclear' => 1, 'multi_hset' => 1, 'multi_hdel' => 1, 'qclear' => 1, 'qpush' => 1, 'qpush_back' => 1, 'qpush_front' => 1, 'qpop' => 1, 'qpop_back' => 1, 'qpop_front' => 1, 'qfront' => 1, 'qback' => 1, 'qget' => 1, 'qset' => 1, 'qtrim_front' => 1, 'qtrim_back' => 1, 'geo_set' => 1, 'geo_del' => 1, 'geo_clear' => 1
    );
    private $read_methods = array(
        'ping' => 1, 'version' => 1, 'dbsize' => 1, 'info' => 1, 'get' => 1, 'exists' => 1, 'getbit' => 1, 'countbit' => 1, 'substr' => 1, 'strlen' => 1, 'keys' => 1, 'scan' => 1, 'smembers' => 1, 'rscan' => 1, 'multi_get' => 1, 'hget' => 1, 'hexists' => 1, 'hsize' => 1, 'hlist' => 1, 'hrlist' => 1, 'hkeys' => 1, 'hgetall' => 1, 'hscan' => 1, 'hrscan' => 1, 'multi_hget' => 1, 'qsize' => 1, 'qlist' => 1, 'qrlist' => 1, 'geo_get' => 1, 'geo_neighbour' => 1, 'geo_distance' => 1
    );

    //是否加入统计队列，因为刷新内存的代码并不需要加入统计队列，否则统计队列会撑挂掉
    public $is_stat = false;

    private $config;//配置
    private $ip;
    private $port;

    /**
     * @param $node
     * @param $singleton
     *
     * @return mixed
     * @author 蔡旭东 mailto:fifsky@dev.ppstream.com
     */
    static public function getInstance($node, $singleton = true) {
        static $cache = array();

        if (!isset($cache[$node]) || !$singleton) {
            $cache[$node]         = new self();
            $cache[$node]->config = config('ssdb', $node);
        }

        return $cache[$node];
    }

    //是否开启监控统计
    public function setStat($is_stat) {
        $this->is_stat = $is_stat;
        return $this;
    }


    private function connect($func) {
        $func   = strtolower($func);
        $config = $this->config[array_rand($this->config)];

        if (!$config) {
            throw new \RuntimeException('ssdb config error.');
        }

        if (isset($config['slave'])) {
            if (isset($this->write_methods[$func])) {
                $server = $config['master'];
            } elseif (isset($this->read_methods[$func])) {
                $server = $config['slave'][array_rand($config['slave'])];
            }
        } else {
            $server = $config['master'];
        }

        if (!isset($server)) {
            throw new \RuntimeException('ssdb server not find.');
        }

        //保持唯一连接
        list($ip, $port, $dbnum) = explode(':', $server);

        $this->ip   = $ip;
        $this->port = $port;

        static $instance_cache = array();

        if ($this->is_stat) {
            $_stat = FStat::getInstance();
        }

        if (!isset($instance_cache[$server])) {
            try {
                $_start_time = microtime(true);


                if (class_exists('\SSDB', false)) {
                    $instance_cache[$server] = new \SSDB();
                } else {
                    $instance_cache[$server] = new SSDBClient();
                }

                $instance_cache[$server]->connect($ip, $port, 1);

                if ($this->is_stat) {
                    $_stat->set(1, 'SSDB连接效率', $_stat->formatTime(number_format(microtime(true) - $_start_time, 6)), $ip . ':' . $port);
                }
            } catch (SSDBException $e) {
                if ($this->is_stat) {
                    $_stat->set(1, 'BUG错误', 'SSDB连接错误', "{$ip}:{$port}");
                }
                throw new SSDBException('SSDB connect error:' . $e->getMessage());
            }
        }

        return $instance_cache[$server];
    }

    private function isConnectionLost(SSDBException $e) {
        if (strpos($e->getMessage(), 'SSDB server went away') !== false || strpos($e->getMessage(), 'Connection lost') !== false || strpos($e->getMessage(), 'Connection refused') !== false) {
            return true;
        }
        return false;
    }

    /**
     * @param $func
     * @param $params
     *
     * @return mixed
     * @throws SSDBException
     */
    public function __call($func, $params) {
        if ($this->is_stat) {
            $_stat = FStat::getInstance();
        }

        $redis_server = $this->connect($func);
        for ($i = 0; $i < 2; $i++) {

            try {
                $_start_time = microtime(true);
                $ret         = call_user_func_array(array($redis_server, $func), $params);
                if ($this->is_stat) {
                    $_stat->set(1, 'SSDB执行效率', $_stat->formatTime(number_format(microtime(true) - $_start_time, 6)), "{$this->ip}:{$this->port}({$func})");
                }
            } catch (SSDBException $e) {
                if ($this->is_stat) {
                    $_stat->set(1, 'BUG错误', 'SSDB执行错误', "{$this->ip}:{$this->port}", $e->getMessage(), 0.1);
                }
                if ($this->isConnectionLost($e)) {
                    $redis_server = $this->connect($func);
                    continue;
                } else {
                    throw new SSDBException('SSDB execute error:' . $e->getMessage());
                }
            }
            break;
        }

        return $ret;
    }
}