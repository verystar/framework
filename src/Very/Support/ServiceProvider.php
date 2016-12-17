<?php

namespace Very\Support;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var \Very\Application
     */
    protected $app;

    /**
     * Indicates if loading of the provider is deferred.
     * TODO 延迟绑定特性尚未实现,后续支持
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Create a new service provider instance.
     *
     * @param  \Very\Application  $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }
}
