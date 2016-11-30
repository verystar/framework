<?php

namespace Very\Cache;

/*
 * Created by JetBrains PhpStorm.ss
 * User: CAIXUDONG
 * Date: 13-1-22
 * Time: 下午5:25
 */

use Very\Library\FStat;

class Redis
{
    //twemproxy不支持multi 此处为了性能进列出了用户信息使用到的hash系列函数，如果需要其他函数，请参照官网添加，必须小写
    //https://github.com/twitter/twemproxy/blob/master/notes/redis.md
    //如果要是用multi则一定是在主节点上操作，并且不再受此类的限制，因为mutil之后返回的是Redis Object
    //注意lpop rpop属于写操作
    private $write_methods = array(
        'incr' => 1, 'incrby' => 1, 'decr' => 1, 'decrby' => 1, 'del' => 1, 'expire' => 1, 'expireat' => 1, 'set' => 1, 'setex' => 1, 'hmset' => 1, 'hsetnx' => 1, 'hincrby' => 1, 'hincrbyfloat' => 1, 'hset' => 1, 'hdel' => 1, 'sadd' => 1, 'multi' => 1, 'exec' => 1, 'sinterstore' => 1, 'sunionstore' => 1, 'rename' => 1, 'lpush' => 1, 'rpush' => 1, 'rpop' => 1, 'lpop' => 1,
    );
    private $read_methods = array(
        'exists' => 1, 'type' => 1, 'ttl' => 1, 'get' => 1, 'hgetall' => 1, 'hlen' => 1, 'hexists' => 1, 'hmget' => 1, 'hget' => 1, 'hkeys' => 1, 'hvals' => 1, 'sismember' => 1, 'smembers' => 1, 'keys' => 1, 'sunion' => 1, 'sdiff' => 1, 'llen' => 1,
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
     *
     * @author 蔡旭东 mailto:fifsky@dev.ppstream.com
     */
    public static function getInstance($node, $singleton = true)
    {
        static $cache = array();

        if (!isset($cache[$node]) || !$singleton) {
            $cache[$node] = new self();
            $cache[$node]->config = config('redis', $node);
        }

        return $cache[$node];
    }

    //是否开启监控统计
    public function setStat($is_stat)
    {
        $this->is_stat = $is_stat;

        return $this;
    }

    private function connect($func)
    {
        $func = strtolower($func);
        $config = $this->config[array_rand($this->config)];

        if (!$config) {
            throw new \RuntimeException('redis config error.');
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
            throw new \RuntimeException('redis server not find.');
        }

        //保持唯一连接
        list($ip, $port, $dbnum) = explode(':', $server);

        $this->ip = $ip;
        $this->port = $port;

        static $redis_cache = array();

        if ($this->is_stat) {
            $_stat = FStat::getInstance();
        }

        if (!isset($redis_cache[$server])) {
            try {
                $_start_time = microtime(true);

                $redis_cache[$server] = new \Redis();
                $redis_cache[$server]->connect($ip, $port, 1);

                if ($dbnum) {
                    $redis_cache[$server]->select($dbnum);
                }

                if ($this->is_stat) {
                    $_stat->set(1, 'Redis连接效率', $_stat->formatTime(number_format(microtime(true) - $_start_time, 6)), $ip.':'.$port);
                }
            } catch (\RedisException $e) {
                if ($this->is_stat) {
                    $_stat->set(1, 'BUG错误', 'Redis连接错误', "{$ip}:{$port}");
                }
                throw new \RedisException('redis connect error:'.$e->getMessage());
            }
        }

        return $redis_cache[$server];
    }

    private function isConnectionLost(\RedisException $e)
    {
        if (strpos($e->getMessage(), 'Redis server went away') !== false || strpos($e->getMessage(), 'Connection lost') !== false || strpos($e->getMessage(), 'read error on connection') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param $func
     * @param $params
     *
     * @return mixed
     *
     * @throws \RedisException
     */
    public function __call($func, $params)
    {
        if ($this->is_stat) {
            $_stat = FStat::getInstance();
        }

        $redis_server = $this->connect($func);
        for ($i = 0; $i < 2; ++$i) {
            try {
                $_start_time = microtime(true);
                $ret = call_user_func_array(array($redis_server, $func), $params);
                if ($this->is_stat) {
                    $_stat->set(1, 'Redis执行效率', $_stat->formatTime(number_format(microtime(true) - $_start_time, 6)), "{$this->ip}:{$this->port}({$func})");
                }
            } catch (\RedisException $e) {
                if ($this->is_stat) {
                    $_stat->set(1, 'BUG错误', 'Redis执行错误', "{$this->ip}:{$this->port}", $e->getMessage(), 0.1);
                }

                logger()->error('Redis exec error',
                                  ["host" => "{$this->ip}:{$this->port}", "msg" => $e->getMessage(), "file" => $e->getFile(), 'line' => $e->getLine()]);

                if ($this->isConnectionLost($e)) {
                    $redis_server = $this->connect($func);
                    continue;
                } else {
                    throw new \RedisException('redis execute error:'.$e->getMessage());
                }
            }
            break;
        }

        return $ret;
    }
}
