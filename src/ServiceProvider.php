<?php

namespace Pmochine\LaravelTongue;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__.'/../config/localization.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('localization.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'localization'
        );

        $this->app->singleton('tongue', function ($app) {
            return new Tongue($app);
        });

        $this->app->singleton('dialect', function ($app) {
            return new Dialect($app);
        });
    }
}
