<?php namespace Very\Validation;

use Very\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('validation', function ($app) {
            return new Validator();
        });
    }
}