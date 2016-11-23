<?php

namespace Very;

/*
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:32
 */
use Very\Http\Request;
use Very\Http\Response;
use Very\Http\Session;
use Very\Http\Cookie;
use Very\Mail\Mailer;
use Very\Container\Container;

class Application extends Container
{
    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace = null;

    /**
     * The Very framework version.
     *
     * @var string
     */
    const VERSION = '2.0.0';
    protected static $instance;

    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        /*
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

        $this->setInstance($this);
    }

    public static function getInstance()
    {
        return static::$instance;
    }

    public static function setInstance(Application $app)
    {
        static::$instance = $app;
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     *
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this['path.app'] = rtrim($basePath);

        foreach (['config', 'views', 'logs'] as $v) {
            $this['path.' . $v] = realpath(rtrim($basePath) . '/' . $v) . DIRECTORY_SEPARATOR;
        }

        $this['namespace.controller'] = '';

        return $this;
    }

    public function setPath($key, $path)
    {
        $this['path.' . $key] = rtrim($path, '/') . DIRECTORY_SEPARATOR;

        return $this;
    }


    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * alias.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->__set($key, $value);
    }

    /**
     * alias.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (!is_null($this->namespace)) {
            return $this->namespace;
        }

        throw new \RuntimeException('Unable to detect application namespace.');
    }
}
