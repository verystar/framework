<?php

namespace Very\Cache;

/*
 * Created by JetBrains PhpStorm
 * User: CAIXUDONG
 * Date: 13-1-22
 * Time: 下午5:25
 */

class Redis
{
    //是否加入统计队列，因为刷新内存的代码并不需要加入统计队列，否则统计队列会撑挂掉
    public  $is_stat = false;
    private $config;//配置
    private $node;//节点

    /**
     * @param $node
     * @param $singleton
     *
     * @return mixed
     */
    public static function getInstance($node, $singleton = true)
    {
        static $cache = array();

        if (!isset($cache[$node]) || !$singleton) {
            $cache[$node]         = new self();
            $cache[$node]->config = config('redis.' . $node);
            $cache[$node]->node   = $node;
        }

        return $cache[$node];
    }

    //是否开启监控统计
    public function setStat($is_stat)
    {
        $this->is_stat = $is_stat;

        return $this;
    }

    private function connect($reconnect = false)
    {
        if (!$this->config) {
            throw new \RuntimeException('redis config error.');
        }

        static $redis_cache = [];

        if (!isset($redis_cache[$this->node]) || $reconnect) {
            try {
                $_start_time = microtime(true);

                $client  = new \Redis();
                $timeout = isset($this->config['timeout']) ? $this->config['timeout'] : 1;
                $client->connect($this->config['host'], $this->config['port'], $timeout);

                if (!empty($this->config['password'])) {
                    $client->auth($this->config['password']);
                }

                if (!empty($this->config['database'])) {
                    $client->select($this->config['database']);
                }

                if (!empty($this->config['prefix'])) {
                    $client->setOption(\Redis::OPT_PREFIX, $this->config['prefix']);
                }

                if (!empty($this->config['read_timeout'])) {
                    $client->setOption(\Redis::OPT_READ_TIMEOUT, $this->config['read_timeout']);
                }

                $redis_cache[$this->node] = $client;

                if ($this->is_stat) {
                    mstat()->set(1, 'Redis连接效率', mstat()->formatTime(number_format(microtime(true) - $_start_time, 6)),
                        $this->config['host'] . ':' . $this->config['port']);
                }
            } catch (\RedisException $e) {
                if ($this->is_stat) {
                    mstat()->set(1, 'BUG错误', 'Redis连接错误', "{$this->config['host']}:{$this->config['port']}");
                }
                throw new \RedisException('redis connect error:' . $e->getMessage());
            }
        }

        return $redis_cache[$this->node];
    }

    private function isConnectionLost(\RedisException $e)
    {
        if (strpos($e->getMessage(), 'Redis server went away') !== false || strpos($e->getMessage(), 'Connection lost') !== false || strpos($e->getMessage(),
                'read error on connection') !== false
        ) {
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
        $redis_server = $this->connect();
        for ($i = 0; $i < 2; ++$i) {
            try {
                $_start_time = microtime(true);
                $ret         = call_user_func_array(array($redis_server, $func), $params);
                if ($this->is_stat) {
                    mstat()->set(1, 'Redis执行效率', mstat()->formatTime(number_format(microtime(true) - $_start_time, 6)),
                        "{$this->config['host']}:{$this->config['port']}({$func})");
                }
            } catch (\RedisException $e) {
                if ($this->is_stat) {
                    mstat()->set(1, 'BUG错误', 'Redis执行错误', "{$this->config['host']}:{$this->config['port']}", $e->getMessage(), 0.1);
                }

                logger()->error('Redis exec error',
                    ["host" => "{$this->config['host']}:{$this->config['port']}", "msg" => $e->getMessage(), "file" => $e->getFile(), 'line' => $e->getLine()]);

                if ($this->isConnectionLost($e)) {
                    $redis_server = $this->connect(true);
                    continue;
                } else {
                    throw new \RedisException('redis execute error:' . $e->getMessage());
                }
            }
            break;
        }

        return $ret;
    }
}