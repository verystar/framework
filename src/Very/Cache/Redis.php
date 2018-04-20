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

    private $config;
    private $node;
    private $throw_exception = true;

    /**
     * @param $node
     * @param $singleton
     *
     * @return $this
     */
    public static function getInstance($node, $singleton = true)
    {
        static $cache = [];

        if (!isset($cache[$node]) || !$singleton) {
            $cache[$node]         = new self();
            $cache[$node]->config = config('redis.'.$node);
            $cache[$node]->node   = $node;
        }

        return $cache[$node];
    }

    public function setThrowException($throw_exception)
    {
        $this->throw_exception = $throw_exception;
    }

    private function connect($reconnect = false)
    {
        if (!$this->config) {
            throw new \InvalidArgumentException('Redis connection not configured.');
        }

        static $redis_cache = [];

        if (!isset($redis_cache[$this->node]) || $reconnect) {
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
        }

        return $redis_cache[$this->node];
    }

    /**
     * @param \RedisException $e
     *
     * @return bool
     * @deprecated
     */
    private function isConnectionLost(\RedisException $e)
    {
        if (strpos($e->getMessage(), 'Redis server went away') !== false
            || strpos($e->getMessage(), 'Connection lost') !== false
            || strpos($e->getMessage(),
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
     * @throws \Exception
     */
    public function __call($func, $params)
    {
        $reconnect = false;
        $ret       = null;
        for ($i = 0; $i < 2; ++$i) {
            try {
                $redis_server = $this->connect($reconnect);
                $ret          = call_user_func_array([$redis_server, $func], $params);
            } catch (\RedisException $e) {
                logger()->error('Redis exec error',
                    [
                        "host" => "{$this->config['host']}:{$this->config['port']}",
                        "msg"  => $e->getMessage(),
                        "file" => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                if ($i == 0) {
                    $reconnect = true;
                    continue;
                } else {
                    if ($this->throw_exception) {
                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                if ($this->throw_exception) {
                    throw $e;
                }
            }
            break;
        }

        return $ret;
    }
}