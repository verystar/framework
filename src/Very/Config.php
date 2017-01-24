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
        $file_name = explode('.', strtolower($key))[0];

        if (!isset(static::$configs[$file_name])) {
            if (file_exists($this->getPath() . $file_name . '.php')) {
                static::$configs[$file_name] = include $this->getPath() . $file_name . '.php';
            } else {
                static::$configs[$file_name] = [];
            }
        }

        return static::$configs[$file_name];
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
        $this->load($key);
        return Arr::get(static::$configs, $key, $default);
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->load($k);
                Arr::set(static::$configs, $k, $v);
            }
        } else {
            $this->load($key);
            Arr::set(static::$configs, $key, $value);
        }
    }

    /**
     * 判断是否存在key.
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return Arr::has(static::$configs, $key);
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
     * @param  string  $key
     * @param  mixed  $value
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
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);
        $array[] = $value;
        $this->set($key, $array);
    }
}
