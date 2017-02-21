<?php

namespace Very;

use ArrayAccess;
use Very\Support\Arr;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:30.
 */
class Config implements ArrayAccess
{
    private static $configs = array();

    private $path;

    public function __construct($path)
    {
        $this->path = realpath($path) . DIRECTORY_SEPARATOR;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * 加载Config.
     *
     * @param $key
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function load($key)
    {
        $keys = explode('.', $key);
        static $dirs = [];

        //多级目录支持
        $file_name = $this->getPath();
        $arr_keys  = [];
        foreach ($keys as $k => $value) {
            $file_name .= $value;
            if (isset($dirs[$file_name]) || is_dir($file_name)) {
                $dirs[$file_name] = 1;
                $file_name .= DIRECTORY_SEPARATOR;
            } else {
                $arr_keys = array_slice($keys, $k + 1);
                break;
            }
        }

        if (!isset(static::$configs[$file_name])) {
            if (file_exists($file_name . '.php')) {
                static::$configs[$file_name] = include $file_name . '.php';
            } else {
                static::$configs[$file_name] = [];
            }
        }

        return [$file_name, $arr_keys ? implode('.', $arr_keys) : null];
    }

    /**
     * 获取配置，支持a.b.c的层级调用.
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $result = $this->load($key);
        return Arr::get(static::$configs[$result[0]], $result[1], $default);
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $result = $this->load($k);
                Arr::set(static::$configs[$result[0]], $result[1], $v);
            }
        } else {
            $result = $this->load($key);
            Arr::set(static::$configs[$result[0]], $result[1], $value);
        }
    }

    /**
     * 判断是否存在key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $result = $this->load($key);
        return Arr::has(static::$configs[$result[0]], $result[1]);
    }


    /**
     * Determine if the given configuration option exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return self::$configs;
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function push($key, $value)
    {
        $array   = $this->get($key);
        $array[] = $value;
        $this->set($key, $array);
    }
}
