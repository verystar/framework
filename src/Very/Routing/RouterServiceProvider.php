<?php namespace Very\Routing;

use Very\Support\ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('router', function ($app) {
            return new Router();
        });
    }
}