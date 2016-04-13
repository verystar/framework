<?php namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:32
 */
use Closure;
use ArrayAccess;
use Very\Http\Request;
use Very\Http\Response;
use Very\Http\Session;
use Very\Http\Cookie;


class Application implements ArrayAccess {

    protected $basePath;

    //保存Application实例
    protected static $instance;

    /**
     * 绑定到容器的内容
     * @var array
     */
    public $bindings = [];

    /**
     * 用来存储在类上的实例，可以是value也可以是是类实例
     * @var array
     */
    protected $instances = [];

    public function __construct($basePath = null) {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        /**
         * 注入基本类库到Application
         */

        $this->singleton('config', function ($app) {
            $env = new Config();
            $env->setPath($app['path.config']);
            return $env;
        });

        $this->singleton('view', function ($app) {
            $env = new View();
            $env->setPath($app['path.views']);
            return $env;
        });

        $this->singleton('request', function ($app) {
            $env = new Request();
            return $env;
        });

        $this->singleton('response', function ($app) {
            $env = new Response();
            return $env;
        });

        $this->singleton('cookie', function ($app) {
            $env = new Cookie();
            return $env;
        });

        $this->singleton('session', function ($app) {
            $env = new Session();
            return $env;
        });

        $this->singleton('router', function ($app) {
            $env = new Router();
            return $env;
        });

        $this->singleton('logger', function ($app) {
            $env = new Logger();
            return $env;
        });

        $env = new Loader();
        $this->singleton('loader', $env);

        $this->setInstance($this);
    }

    public static function getInstance() {
        return static::$instance;
    }

    public static function setInstance(Application $app) {
        static::$instance = $app;
    }


    /**
     * 注入一个类到容器
     *
     * @param  string|array         $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool                 $shared
     *
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false) {

        unset($this->instances[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = function ($app) use ($concrete) {
                $object      = new $concrete;
                $object->app = $app;
                return $object;
            };
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }


    /**
     * make 容器
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, $parameters = []) {
        //如果已经实例化则返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }


        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
            $object   = $concrete($this, $parameters);
        } else {
            $object = false;
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }


    public function isShared($abstract) {
        if (isset($this->bindings[$abstract]['shared'])) {
            $shared = $this->bindings[$abstract]['shared'];
        } else {
            $shared = false;
        }

        return isset($this->instances[$abstract]) || $shared === true;
    }

    public function singleton($abstract, $concrete) {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Set the base path for the application.
     *
     * @param  string $app_path
     *
     * @return $this
     */
    public function setBasePath($app_path) {

        $this['path']     = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this['path.app'] = $app_path;

        foreach (['config', 'views', 'modules', 'helpers', 'logs'] as $v) {
            $this['path.' . $v] = realpath($app_path . '/' . $v) . DIRECTORY_SEPARATOR;
        }
        return $this;
    }

    public function setPath($key, $path) {
        $this['path.' . $key] = rtrim($path, '/') . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function offsetExists($key) {
        return isset($this->instances[$key]);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value) {
        // If the value is not a Closure, we will make it one. This simply gives
        // more "drop-in" replacement functionality for the Pimple which this
        // container's simplest functions are base modeled and built after.
        if (!$value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this->bind($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset($key) {
        unset($this->bindings[$key], $this->instances[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key) {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($key, $value) {
        $this[$key] = $value;
    }

    /**
     * alias
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function set($key, $value) {
        $this->__set($key, $value);
    }

    /**
     * alias
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key) {
        return $this->__get($key);
    }
}