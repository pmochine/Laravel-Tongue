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

        // Scoped, so Octane builds Tongue again with the application of each request.
        $this->app->scoped(Tongue::class, function ($app) {
            return new Tongue($app);
        });
        $this->app->alias(Tongue::class, 'tongue');

        // A singleton, because it remembers the routes from interpret() for the lifetime of the app.
        $this->app->singleton(Dialect::class, function ($app) {
            return new Dialect($app);
        });
        $this->app->alias(Dialect::class, 'dialect');
    }
}
