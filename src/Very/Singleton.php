<?php

namespace Very;

/*
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午5:40
 */

use RuntimeException;

class Singleton
{
    private $model_instance;

    private $cache = array();

    /**
     * @param object $model_instance 必须是一个类的实例
     *
     * @return Singleton
     *
     * @throws Exception
     */
    public static function getInstance($model_instance)
    {
        if (!is_object($model_instance)) {
            throw new \RuntimeException('Singleton instance param must is object');
        }

        $_instance = new self();
        $_instance->model_instance = $model_instance;

        return $_instance;
    }

    /**
     * 清空单例缓存.
     */
    public function clear()
    {
        $this->cache = array();
    }

    /**
     * eg:在控制层调用model某个方法，希望全局执行一次，第二次执行直接返回结果，类似于在方法里面加入static单例
     * $this->model('user')->singleton()->getUser('1');
     * 如果不使用singleton，再次调用getUser的时候会重复执行getUser内的代码，如sql，而使用singleton之后第二次调用则不会.
     *
     * @param $fun
     * @param $args
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function __call($fun, $args)
    {
        if (!is_callable(array($this->model_instance, $fun)) || $fun === 'singleton') {
            throw new \RuntimeException('Singleton model method not found:'.$fun);
        }

        $md5 = md5(get_class($this->model_instance).$fun.json_encode($args));

        if (!array_key_exists($md5, $this->cache)) {
            switch (count($args)) {
                case 0:
                    $this->cache[$md5] = $this->model_instance->$fun();
                    break;

                case 1:
                    $this->cache[$md5] = $this->model_instance->$fun($args[0]);
                    break;

                case 2:
                    $this->cache[$md5] = $this->model_instance->$fun($args[0], $args[1]);
                    break;

                case 3:
                    $this->cache[$md5] = $this->model_instance->$fun($args[0], $args[1], $args[2]);
                    break;

                case 4:
                    $this->cache[$md5] = $this->model_instance->$fun($args[0], $args[1], $args[2], $args[3]);
                    break;

                default:
                    $this->cache[$md5] = call_user_func_array(array($this->model_instance, $fun), $args);
            }
        }

        return $this->cache[$md5];
    }
}
