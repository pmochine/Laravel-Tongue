<?php

namespace Pmochine\LaravelTongue;

use Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__.'/../config/localization.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('localization.php'),
        ], 'config');

        // Since Laravel 11 there is no app/Http/Kernel.php. Keep an alias that the app defines itself.
        $router = $this->app['router'];

        if (! array_key_exists('speaks-tongue', $router->getMiddleware())) {
            $router->aliasMiddleware('speaks-tongue', TongueSpeaksLocale::class);
        }
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

        // Tongue keeps no state. Scoped, so a queue job or an Octane request starts with a new instance.
        $this->app->scoped(Tongue::class);
        $this->app->alias(Tongue::class, 'tongue');

        // A singleton, because it remembers the routes from interpret() for the lifetime of the app.
        $this->app->singleton(Dialect::class);
        $this->app->alias(Dialect::class, 'dialect');
    }
}
