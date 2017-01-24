<?php namespace Very\Support;

class StatServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mstat', function ($app) {
            return new Stat($app['config']['fstat']);
        });
    }
}