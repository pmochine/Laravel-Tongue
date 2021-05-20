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
        /*
        * Register the service provider for the dependency.
        https://github.com/kevindierkx/laravel-domain-parser
        */
        $this->app->register(\Bakame\Laravel\Pdp\ServiceProvider::class);
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('TopLevelDomains', \Bakame\Laravel\Pdp\Facades\TopLevelDomains::class);

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
