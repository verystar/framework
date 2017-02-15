<?php

namespace Very\Translation;

use Very\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('translator', function ($app) {
            $locale = $app['config']['app.locale'];
            $trans  = new Translator($locale);
            return $trans;
        });
    }
}
