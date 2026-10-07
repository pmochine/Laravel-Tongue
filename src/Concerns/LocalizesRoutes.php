<?php

namespace Pmochine\LaravelTongue\Concerns;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Pmochine\LaravelTongue\Misc\Config;
use SplObjectStorage;

/**
 * Registers translated routes once per locale, each with the path of its locale.
 * The routes do not depend on the locale of the request that registers them,
 * so route:cache and Laravel Octane work.
 *
 * Each route keeps its data in the route action under the key "tongue":
 * the id of its group, its locales, the translation key and its name from the route file.
 */
trait LocalizesRoutes
{
    /**
     * The locale of the current round of localizedRoutes(), or null outside of it.
     *
     * @var string|null
     */
    protected $routesLocale;

    /**
     * The paths that interpret() gave in the current round.
     *
     * @var array [path, like "admin/hallo/{user}" => [translation keys]]
     */
    protected $routesLocalePaths = [];

    /**
     * @var array [group id => [locale => route]]
     */
    protected $localizedRouteIndex = [];

    /**
     * @var string|null
     */
    protected $localizedRouteIndexSignature;

    /**
     * Registers the routes of the callback once for each supported locale.
     * In the callback, interpret() gives the path of that locale.
     *
     * @param  \Closure  $routes
     * @return void
     */
    public function localizedRoutes(Closure $routes): void
    {
        $router = app('router');
        $groups = [];

        foreach ($this->routeLocales() as $locale) {
            $before = $this->registeredRoutes();

            $this->routesLocale = $locale;
            $this->routesLocalePaths = [];

            try {
                $routes();
            } finally {
                $this->routesLocale = null;
            }

            $after = $this->registeredRoutes();

            foreach ($router->getRoutes()->getRoutes() as $route) {
                if ($before->contains($route)) {
                    continue;
                }

                $id = $this->localizedRouteId($route);
                $locales = [$locale];

                // The same path as in an earlier locale replaced the route of that locale.
                // So this route serves both locales.
                foreach ($groups[$id] ?? [] as $index => $earlier) {
                    if (! $after->contains($earlier)) {
                        $locales = array_merge($earlier->getAction('tongue')['locales'], $locales);
                        unset($groups[$id][$index]);
                    }
                }

                $this->markLocalizedRoute($route, $id, $locales);
                $groups[$id][] = $route;
            }
        }

        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    /**
     * The route of the same group in the given locale, for a route from localizedRoutes().
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $locale
     * @return \Illuminate\Routing\Route|null
     */
    protected function localizedSibling(Route $route, $locale)
    {
        $tongue = $route->getAction('tongue');

        if (! is_array($tongue)) {
            return null;
        }

        return $this->localizedRouteIndex()[$tongue['id']][$locale] ?? null;
    }

    /**
     * The route from localizedRoutes() that got its path from the translation key, in the given locale.
     *
     * @param  string|false|null  $translationKey
     * @param  string  $locale
     * @return \Illuminate\Routing\Route|null
     */
    protected function localizedRouteByKey($translationKey, $locale)
    {
        if (! is_string($translationKey) || $translationKey === '') {
            return null;
        }

        foreach ($this->localizedRouteIndex() as $routes) {
            if ((reset($routes)->getAction('tongue')['key'] ?? null) === $translationKey) {
                return $routes[$locale] ?? null;
            }
        }

        return null;
    }

    /**
     * For the middleware: the URL of the current route in the current locale,
     * if the request used the path of another locale, like de.example.com/hello.
     *
     * @return string|null
     */
    public function localizedRouteRedirectUrl(): ?string
    {
        $route = app('router')->current();
        $tongue = $route ? $route->getAction('tongue') : null;
        $locale = tongue()->current();

        if (! is_array($tongue) || in_array($locale, $tongue['locales'], true)) {
            return null;
        }

        $path = $this->localizedRoutePath($route, $locale);

        if ($path === false) {
            return null;
        }

        $query = request()->server('QUERY_STRING');

        return $this->assembleUrl($this->addLocaleToHost($locale), $path, is_string($query) && $query !== '' ? $query : null);
    }

    /**
     * The fallback locale first, so its routes keep their names.
     *
     * @return array
     */
    protected function routeLocales()
    {
        $locales = array_keys(Config::supportedLocales() ?: []);
        $fallback = Config::fallbackLocale();

        if (in_array($fallback, $locales, true)) {
            array_unshift($locales, $fallback);
        }

        return array_values(array_unique($locales));
    }

    /**
     * @return \SplObjectStorage
     */
    protected function registeredRoutes()
    {
        $routes = new SplObjectStorage();

        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $routes->attach($route);
        }

        return $routes;
    }

    /**
     * The id is the same for the routes of all locales: the methods, the domain,
     * and the prefix with the translation key, or the path if the route has no translation.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function localizedRouteId(Route $route)
    {
        $key = $this->localizedRouteKey($route);
        $where = $key !== null
            ? 'key:'.trim((string) $route->getPrefix(), '/').'|'.$key
            : 'path:'.$this->normalizePath($route->uri());

        return implode(',', $route->methods()).'|'.$route->getDomain().'|'.$where;
    }

    /**
     * The translation key that interpret() gave the path of the route in the current round.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string|null
     */
    protected function localizedRouteKey(Route $route)
    {
        $keys = $this->routesLocalePaths[$this->normalizePath($route->uri())] ?? [];

        if (in_array($route->getName(), $keys, true)) {
            return $route->getName();
        }

        return $keys[0] ?? null;
    }

    /**
     * Saves the group and the locales in the route action.
     * Route names must be unique for route:cache, so only the route of the fallback locale keeps its name.
     * The routes of the other locales get the locale as suffix, like "welcome.de".
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $id
     * @param  array  $locales
     * @return void
     */
    protected function markLocalizedRoute(Route $route, $id, array $locales)
    {
        $action = $route->getAction();
        $name = $action['tongue']['name'] ?? ($action['as'] ?? null);
        $locales = array_values(array_unique($locales));

        $action['tongue'] = [
            'id' => $id,
            'locales' => $locales,
            'key' => $this->localizedRouteKey($route),
            'name' => $name,
        ];

        if ($name !== null) {
            $action['as'] = in_array(Config::fallbackLocale(), $locales, true) ? $name : $name.'.'.$locales[0];
        }

        $route->setAction($action);
    }

    /**
     * @return array [group id => [locale => route]]
     */
    protected function localizedRouteIndex()
    {
        $routes = app('router')->getRoutes();

        // A compiled route collection from route:cache does not change. A normal one can get new routes.
        $signature = spl_object_id($routes).($routes instanceof RouteCollection ? ':'.count($routes->getRoutes()) : '');

        if ($this->localizedRouteIndexSignature !== $signature) {
            $this->localizedRouteIndex = [];

            foreach ($routes->getRoutes() as $route) {
                $tongue = $route->getAction('tongue');

                foreach (is_array($tongue) ? $tongue['locales'] : [] as $locale) {
                    $this->localizedRouteIndex[$tongue['id']][$locale] = $route;
                }
            }

            $this->localizedRouteIndexSignature = $signature;
        }

        return $this->localizedRouteIndex;
    }
}
