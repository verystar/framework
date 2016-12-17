<?php namespace Very\Http;

use Very\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRequest();
        $this->registerResponse();
    }

    private function registerRequest()
    {
        $this->app->singleton('request', function ($app) {
            return new Request();
        });
    }

    private function registerResponse()
    {
        $this->app->singleton('response', function ($app) {
            return new Response();
        });
    }
}