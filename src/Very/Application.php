<?php

namespace Very;

/*
 * Created by PhpStorm.
 * User: fifsky
 * Date: 15/2/13 下午11:32
 */
use Illuminate\Container\Container;
use Very\Support\Arr;

class Application extends Container
{

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace = null;

    protected $serviceProviders = [];
    protected $loadedProviders  = [];

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        \Very\Exceptions\HandleExceptions::class,
    ];

    /**
     * The Very framework version.
     *
     * @var string
     */
    const VERSION = '3.0.9';

    public function __construct($basePath = null)
    {
        static::setInstance($this);

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        if (!defined('ENVIRON')) {
            define('ENVIRON', (getenv('APP_ENV') ? getenv('APP_ENV') : 'pro'));
        }

        /*
         * Bind base library
         */
        $this->singleton('config', function ($app) {
            $config = new Config($app['path.config']);
            date_default_timezone_set($config->get('app.timezone', 'Asia/Shanghai'));

            return $config;
        });

        $this->singleton('logger', function ($app) {
            return new Logger($app['path.logs'], $app['config']['app.log_max_files']);
        });

        $this->singleton('view', function ($app) {
            $env = new View();
            $env->setPath($app['path.views']);

            return $env;
        });

        $this->registerAppProviders();
        $this->registerCoreContainerAliases();
        $this->bootstrapWith();
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
            $this['path.'.$v] = realpath(rtrim($basePath).'/'.$v).DIRECTORY_SEPARATOR;
        }

        $this['namespace.controller'] = '';

        return $this;
    }

    public function setPath($key, $path)
    {
        $this['path.'.$key] = rtrim($path, '/').DIRECTORY_SEPARATOR;

        return $this;
    }

    public function registerAppProviders()
    {
        if ($this['config']['app.providers']) {
            foreach ($this['config']['app.providers'] as $provider) {
                $this->register($this->resolveProviderClass($provider));
            }
        }
    }

    /**
     * Alias a type to a different name.
     *
     * @param  string $abstract
     * @param  string $alias
     *
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        $aliases = [
            'app'     => [\Very\Application::class, \Illuminate\Container\Container::class],
            'config'  => [\Very\Config::class],
            'cookie'  => [\Very\Cookie\CookieJar::class],
            'logger'  => [\Very\Logger::class],
            'router'  => [\Very\Routing\Router::class],
            'session' => [\Very\Session\SessionManager::class],
            'view'    => [\Very\View::class],
        ];

        foreach ($aliases as $key => $aliase) {
            foreach ($aliase as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Very\Support\ServiceProvider|string $provider
     *
     * @return \Very\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::first($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     *
     * @return \Very\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Very\Support\ServiceProvider $provider
     *
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $class                         = get_class($provider);
        $this->serviceProviders[]      = $provider;
        $this->loadedProviders[$class] = true;
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Very\Support\ServiceProvider|string $provider
     * @param  array                                $options
     * @param  bool                                 $force
     *
     * @return \Very\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        return $provider;
    }

    /**
     * Run the given array of bootstrap classes.
     *
     * @return void
     */
    public function bootstrapWith()
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
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

        if ($this['namespace']) {
            return $this->namespace = $this['namespace'];
        }

        throw new \RuntimeException('Unable to detect application namespace.');
    }
}