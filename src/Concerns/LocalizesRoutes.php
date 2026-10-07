<?php

namespace Pmochine\LaravelTongue\Concerns;

use Closure;
use Exception;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use LogicException;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Misc\Url;

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
     * Each round registers into an empty route collection, so the n-th route of every
     * round belongs to the same route definition, whatever its path or prefix is.
     *
     * @param  \Closure  $routes
     * @return void
     *
     * @throws \LogicException [if two route definitions get the same path, or a route name is taken]
     */
    public function localizedRoutes(Closure $routes): void
    {
        $router = app('router');
        $collection = $router->getRoutes();
        $locales = $this->routeLocales();
        $rounds = [];

        foreach ($locales as $locale) {
            $round = new RouteCollection();
            $router->setRoutes($round);

            $this->routesLocale = $locale;
            $this->routesLocalePaths = [];

            try {
                $routes();
            } finally {
                $this->routesLocale = null;
                $router->setRoutes($collection);
            }

            $rounds[$locale] = $this->withTranslationKeys($round->getRoutes());
        }

        $this->addLocalizedRoutes($collection, $rounds, $locales);
    }

    /**
     * Pairs the routes of the rounds and adds them to the route collection of the app.
     * Locales with the same path share one route.
     *
     * @param  \Illuminate\Routing\RouteCollection  $collection
     * @param  array  $rounds  [locale => [[route, candidate keys, key by position]]]
     * @param  array  $locales
     * @return void
     */
    protected function addLocalizedRoutes(RouteCollection $collection, array $rounds, array $locales)
    {
        $count = count(reset($rounds) ?: []);

        foreach ($rounds as $routes) {
            if (count($routes) !== $count) {
                throw new LogicException('localizedRoutes() must register the same routes in every locale.');
            }
        }

        $call = bin2hex(random_bytes(4));

        // Names that the route file set after it added a route are not in the lookups yet
        $collection->refreshNameLookups();

        for ($index = 0; $index < $count; $index++) {
            $id = "{$call}.{$index}";
            $variants = [];

            foreach ($locales as $locale) {
                $route = $rounds[$locale][$index][0];
                $path = implode(',', $route->methods()).' '.$route->getDomain().'/'.$route->uri();

                $variants[$path] = $variants[$path] ?? ['route' => $route, 'locales' => []];
                $variants[$path]['locales'][] = $locale;
            }

            $key = $this->translationKeyOf(array_column($rounds, $index), reset($variants)['route']->getName());

            foreach ($variants as $variant) {
                $this->rejectPathConflicts($collection, $variant['route'], $id);
                $this->markLocalizedRoute($collection, $variant['route'], $id, $variant['locales'], $locales[0], $key);
                $collection->add($variant['route']);
            }
        }

        $collection->refreshNameLookups();
        $collection->refreshActionLookups();
    }

    /**
     * Gives each route of a round the translation keys that interpret() gave its path.
     * Routes with the same path, like GET and POST "contact", get all keys of the path,
     * and the key in the order of interpret() as a hint.
     *
     * @param  array  $routes
     * @return array [[route, candidate keys, key by position]]
     */
    protected function withTranslationKeys(array $routes)
    {
        $used = [];

        return array_map(function (Route $route) use (&$used) {
            $path = $this->normalizePath($route->uri());
            $keys = $this->routesLocalePaths[$path] ?? [];
            $position = $used[$path] = ($used[$path] ?? -1) + 1;

            return [$route, array_values(array_unique($keys)), $keys[$position] ?? null];
        }, $routes);
    }

    /**
     * The translation key of a route definition: the key that gives its path in every locale.
     * If several keys fit, the route name or the order of interpret() decides.
     *
     * @param  array  $variants  [[route, candidate keys, key by position]] of one definition, one per locale
     * @param  string|null  $name
     * @return string|null
     */
    protected function translationKeyOf(array $variants, $name)
    {
        $keys = null;

        foreach ($variants as [$route, $candidates]) {
            $keys = $keys === null ? $candidates : array_values(array_intersect($keys, $candidates));
        }

        if (! $keys) {
            return null;
        }

        if (count($keys) === 1) {
            return $keys[0];
        }

        if (in_array($name, $keys, true)) {
            return $name;
        }

        $byPosition = $variants[0][2] ?? null;

        return in_array($byPosition, $keys, true) ? $byPosition : $keys[0];
    }

    /**
     * Laravel finds a route by its method and path, not by the locale. So two localized route
     * definitions can not share a path, also not from two calls of localizedRoutes().
     *
     * @param  \Illuminate\Routing\RouteCollection  $collection
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $id
     * @return void
     *
     * @throws \LogicException
     */
    protected function rejectPathConflicts(RouteCollection $collection, Route $route, $id)
    {
        $routes = $collection->getRoutesByMethod();
        $path = $route->getDomain().$route->uri();

        foreach ($route->methods() as $method) {
            $existing = $routes[$method][$path] ?? null;
            $existingId = $existing ? ($existing->getAction('tongue')['id'] ?? null) : null;

            if ($existingId !== null && $existingId !== $id) {
                throw new LogicException("Two routes in localizedRoutes() have the same path [{$method} {$path}] in different locales.");
            }
        }
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

        // Like twister(): Tongue does not redirect a whitelisted subdomain, like admin.example.com
        if (Url::hasSubdomain() && tongue()->speaking('subdomains', Url::subdomain())) {
            return null;
        }

        // The middleware runs before route model binding. Bind a copy in a locale of the route,
        // so a translated slug in the URL gives its key in the current locale. Locales that share
        // a path can have different slugs, so try each of them.
        $bound = $route;

        foreach ($tongue['locales'] as $source) {
            $copy = $this->inLocale($source, function () use ($route) {
                return $this->boundCopy($route);
            });

            if ($copy !== null) {
                $bound = $copy;
                break;
            }
        }

        $path = $this->localizedRoutePath($bound, $locale);

        if ($path === false) {
            return null;
        }

        $query = request()->server('QUERY_STRING');

        // Only the path changes. twister() already checked the host, which can also be an alias.
        return $this->assembleUrl(request()->getHost(), $path, is_string($query) && $query !== '' ? $query : null);
    }

    /**
     * A copy of the route with route model binding, or null if the binding fails.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route|null
     */
    protected function boundCopy(Route $route)
    {
        $copy = clone $route;

        try {
            app('router')->substituteBindings($copy);
            app('router')->substituteImplicitBindings($copy);
        } catch (Exception $e) {
            // For example a slug that does not exist in this locale
            return null;
        }

        return $copy;
    }

    /**
     * The fallback locale first, so its routes keep their names.
     * Without a supported fallback locale, the first supported locale keeps the names.
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
     * Saves the group and the locales in the route action.
     * Route names must be unique for route:cache, so only the route of the first locale keeps its name.
     * The routes of the other locales get the locale as suffix, like "welcome.de".
     *
     * @param  \Illuminate\Routing\RouteCollection  $collection
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $id
     * @param  array  $locales
     * @param  string  $firstLocale  [the fallback locale, if it is supported]
     * @param  string|null  $key
     * @return void
     */
    protected function markLocalizedRoute(RouteCollection $collection, Route $route, $id, array $locales, $firstLocale, $key)
    {
        $action = $route->getAction();
        $name = $action['as'] ?? null;

        $action['tongue'] = ['id' => $id, 'locales' => $locales, 'key' => $key, 'name' => $name];

        if ($name !== null) {
            $action['as'] = in_array($firstLocale, $locales, true) ? $name : $name.'.'.$locales[0];

            if ($collection->hasNamedRoute($action['as'])) {
                throw new LogicException("The route name [{$action['as']}] of a route in localizedRoutes() is taken. Rename one of the routes.");
            }
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
