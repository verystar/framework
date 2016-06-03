<?php

namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:30.
 */
class Config
{
    private static $configs = array();

    private $path;

    public function setPath($path)
    {
        $this->path = realpath($path).DIRECTORY_SEPARATOR;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * 加载Config.
     *
     * @param $config
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function load($config)
    {
        $config = strtolower($config);

        if (!isset(static::$configs[$config])) {
            if (file_exists($this->getPath().$config.'.php')) {
                static::$configs[$config] = include $this->getPath().$config.'.php';
            } else {
                static::$configs[$config] = [];
            }
        }

        return static::$configs[$config];
    }

    /**
     * 获取配置，支持a.b.c的层级调用.
     *
     * @param      $config
     * @param      $name
     * @param null $default
     *
     * @return mixed
     */
    public function get($config, $name = null, $default = null)
    {
        if (!is_array($config)) {
            $config = $this->load($config);
        }

        if ($name === null) {
            return $default === null ? $config : $default;
        }

        return array_get($config, $name, $default);
    }

    public function set($config, $key, $value = null)
    {
        if (!isset(static::$configs[$config])) {
            $this->load($config);
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                array_set(static::$configs[$config], $k, $v);
            }
        } else {
            array_set(static::$configs[$config], $key, $value);
        }
    }

    /**
     * 判断是否存在key.
     *
     * @param string $config
     * @param string $key
     *
     * @return bool
     */
    public function has($config, $key)
    {
        $default = microtime(true);

        return $this->get($config, $key, $default) !== $default;
    }
}
