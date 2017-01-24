<?php namespace Very\Session;

use Very\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSessionManager();
    }

    private function registerSessionManager()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app['config']['session']);
        });
    }
}