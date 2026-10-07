<?php

namespace Pmochine\LaravelTongue;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pmochine\LaravelTongue\Accent\Accent;
use Pmochine\LaravelTongue\Contracts\LocalizedUrlRoutable;
use Pmochine\LaravelTongue\Localization\Localization;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Misc\Url;

/**
 * This class was written by
 * https://github.com/hoyvoy/laravel-subdomain-localization
 * Now is the time to have my own dialect with it :P.
 */
class Dialect
{
    /**
     * The routes from interpret(), in the order of interpret().
     *
     * @var array [['path' => path like Route::uri() stores it, 'key' => translation key, 'prefix' => prefix of the route group]]
     */
    protected $interpretedRoutes = [];

    /**
     * Adds the detected locale to the current unlocalized URL.
     *
     * @return string
     */
    public function redirectUrl($url = null, ?string $locale = null)
    {
        $parsed_url = parse_url($url ?? request()->fullUrl());

        $domain = Url::domain();

        if (Config::beautify() && tongue()->current() === Config::fallbackLocale()) {
            $parsed_url['host'] = $domain;
        } else {
            $parsed_url['host'] = Url::localeSubdomain(tongue()->current()).'.'.$domain;
        }

        if ($locale) {
            $parsed_url['host'] = Url::localeSubdomain($locale).'.'.$domain;
        }

        return Accent::unparseUrl($parsed_url);
    }

    /**
     * Creates the redirect response.
     *
     * @param  string
     * @return \Illuminate\Http\RedirectResponse;
     */
    public function redirect(string $redirection)
    {
        // Save any flashed data for redirect
        app('session')->reflash();

        return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
    }

    /**
     * Translate the current route for the given locale.
     *
     * @param $locale
     * @return bool|string
     */
    public function current($locale)
    {
        $route = app('router')->current();

        // Without a route or a translation, the URL keeps the path of the current request.
        $path = $route ? $this->localizedRoutePath($route, $locale) : false;

        return $this->assembleUrl($this->addLocaleToHost($locale), $path);
    }

    /**
     * The path of a bound route in the given locale, with its parameters.
     *
     * @param  \Illuminate\Routing\Route  $route  [the bound route]
     * @param  string  $locale
     * @param  \Illuminate\Routing\Route|null  $registered  [the route of the app, if $route is a copy with another path]
     * @return string|false [false, if the route has no translation and no parameter with a key per locale]
     */
    protected function localizedRoutePath(Route $route, $locale, ?Route $registered = null)
    {
        $registered = $registered ?: $route;

        // A route name that is a translation key, like "routes.welcome"
        $path = $this->translatedKeyPath($registered->getName(), $locale, $registered);

        // A route with a path from interpret()
        if ($path === false) {
            $path = $this->translatedRoutePath($registered, $locale);
        }

        // A route without translation keeps its path. Only a translated slug changes it.
        if ($path === false && ! $this->hasLocalizedParameter($route)) {
            return false;
        }

        return Accent::substituteAttributesInRoute($this->routeAttributes($route, $locale), $path !== false ? $path : $registered->uri());
    }

    /**
     * The parameters of a bound route as they are in the URL, before route model binding.
     * A model that implements LocalizedUrlRoutable gives its route key in the locale.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $locale
     * @return array
     */
    protected function routeAttributes(Route $route, $locale)
    {
        $attributes = array_filter($route->originalParameters(), function ($value) {
            return ! is_null($value);
        });

        foreach ($route->parameters() as $name => $value) {
            $key = $value instanceof LocalizedUrlRoutable ? $value->getLocalizedRouteKey($locale) : null;

            // Without a key in the locale, the parameter keeps its value from the URL
            if ($key !== null && $key !== '') {
                $attributes[$name] = $key;
            }
        }

        return $attributes;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    protected function hasLocalizedParameter(Route $route)
    {
        foreach ($route->parameters() as $value) {
            if ($value instanceof LocalizedUrlRoutable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replaces each model that implements LocalizedUrlRoutable with its route key in the locale, if it has one.
     *
     * @param  array  $attributes
     * @param  string  $locale
     * @return array
     */
    protected function localizeAttributes(array $attributes, $locale)
    {
        return array_map(function ($value) use ($locale) {
            $key = $value instanceof LocalizedUrlRoutable ? $value->getLocalizedRouteKey($locale) : null;

            // Without a key in the locale, Laravel uses the route key of the model
            return $key !== null && $key !== '' ? $key : $value;
        }, $attributes);
    }

    /**
     * Get all Translations for the current URL.
     *
     * @param  bool  $excludeCurrentLocale
     * @return array
     */
    public function translateAll($excludeCurrentLocale = true)
    {
        $versions = [];

        foreach (tongue()->speaking()->keys()->all() as $locale) {
            if ($excludeCurrentLocale && $locale == tongue()->current()) {
                continue;
            }

            if ($url = $this->current($locale)) {
                $versions[$locale] = $url;
            }
        }

        return $versions;
    }

    /**
     * The URLs of the current page in all locales, for <link rel="alternate" hreflang="...">.
     * The keys are hreflang values, like "de" or "pt-BR", and "x-default" for the fallback locale.
     * A locale can set its own value with the key "hreflang" in supportedLocales.
     * Each URL is the address of the locale itself, with the query string of the current page.
     *
     * @return array
     */
    public function alternates(): array
    {
        $route = app('router')->current();
        $alternates = [];

        foreach (tongue()->speaking()->all() as $locale => $properties) {
            $hreflang = is_array($properties) && ! empty($properties['hreflang'])
                ? $properties['hreflang']
                : str_replace('_', '-', $locale);

            $alternates[$hreflang] = $this->canonicalUrl($route, $locale);
        }

        $alternates['x-default'] = $this->canonicalUrl($route, Config::fallbackLocale());

        return $alternates;
    }

    /**
     * The URL of the current page in the locale, without a redirect by the middleware.
     *
     * @param  \Illuminate\Routing\Route|null  $route
     * @param  string  $locale
     * @return string
     */
    protected function canonicalUrl(?Route $route, $locale)
    {
        $path = $route ? $this->localizedRoutePath($route, $locale) : false;

        // The query string decides the content too, like ?page=2 of a pagination
        $query = request()->server('QUERY_STRING') ?: null;

        // Unlike a link of the language switcher, the fallback locale has no subdomain for the cookie
        if (Config::beautify() && $locale === Config::fallbackLocale()) {
            return $this->assembleUrl(Url::domain(), $path, $query);
        }

        return $this->assembleUrl(Url::localeSubdomain($locale).'.'.Url::domain(), $path, $query);
    }

    /**
     * Translates a URL of the app into the given locale, like the URL of the previous page.
     * The URL keeps its query string. Without a matching route, only the subdomain changes.
     * A relative URL is relative to the app, like in url().
     *
     * @param  string  $url
     * @param  string|null  $locale  [the current locale, if null]
     * @return string
     */
    public function translateUrl(string $url, ?string $locale = null): string
    {
        $locale = $locale ?: tongue()->current();
        $url = $this->absoluteUrl($url);

        // Like the request of the app, so an app in a subfolder finds its routes
        $request = Request::create($url, 'GET', [], [], [], Arr::only(request()->server->all(), ['SCRIPT_FILENAME', 'SCRIPT_NAME']));

        // The locale of the URL, like "de" for de.example.com. The bare domain has the fallback locale.
        $urlLocale = Url::localeOfHost($request->getHost()) ?? Config::fallbackLocale();

        // Route model binding finds a translated slug only in the locale of the URL
        $routes = $this->inLocale($urlLocale, function () use ($request, $urlLocale) {
            return $this->findRouteByRequest($request, $urlLocale);
        });

        $path = $routes ? $this->localizedRoutePath($routes[0], $locale, $routes[1]) : false;

        $parsed_url = parse_url($url) ?: [];
        $parsed_url['host'] = $this->addLocaleToHost($locale);

        if ($path !== false) {
            $parsed_url['path'] = $this->withBasePath($path, $request->getBaseUrl());
        }

        return $this->unparseUrlWithoutTrailingSlash($parsed_url);
    }

    /**
     * Return translated URL from route.
     * The route name can be a translation key, like "routes.welcome", or the name of a route, like "welcome".
     *
     * @param  string  $routeName
     * @param array]null]bool $routeAttributes
     * @param  string|false  $locale
     * @return string|bool
     */
    public function translate($routeName, $routeAttributes = null, $locale = null)
    {
        // If no locale is given, we use the current locale
        if (! $locale) {
            $locale = tongue()->current();
        }

        $attributes = $this->localizeAttributes(is_iterable($routeAttributes) ? collect($routeAttributes)->all() : [], $locale);
        $route = $this->findRouteByName($routeName);
        $bindingFields = $route ? $route->bindingFields() : [];

        // A translation key, like "routes.welcome"
        $path = $this->translatedKeyPath($routeName, $locale, $route);

        // A route with a path from interpret()
        if ($path === false && $route) {
            $path = $this->translatedRoutePath($route, $locale);
        }

        // A route without translation: Laravel builds the path, like route()
        if ($path === false && $route) {
            return $this->buildUrlFromRoute($locale, $route, $attributes);
        }

        return $this->buildUrl($locale, $path, $attributes, $bindingFields);
    }

    /**
     * Builds the URL for the locale. Without a path, the URL keeps the path of the current request.
     *
     * @param  string  $locale
     * @param  string|false  $path  [a route path with placeholders, like "hello/{user}"]
     * @param  array  $attributes
     * @param  array  $bindingFields
     * @return string
     */
    protected function buildUrl($locale, $path, array $attributes, array $bindingFields = [])
    {
        if ($path !== false) {
            // Substitute the attributes and remove the missing optional ones
            $path = Accent::substituteAttributesInRoute($attributes, $path, $bindingFields);
        }

        return $this->assembleUrl($this->addLocaleToHost($locale), $path);
    }

    /**
     * Builds the URL of a route without translation with route() of Laravel.
     *
     * @param  string  $locale
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $attributes
     * @return string
     */
    protected function buildUrlFromRoute($locale, Route $route, array $attributes)
    {
        try {
            $relativeUrl = app('url')->toRoute($route, $attributes, false);
        } catch (UrlGenerationException $e) {
            // A required attribute is missing. The placeholder stays in the path, like before version 6.
            return $this->buildUrl($locale, $route->uri(), $attributes, $route->bindingFields());
        }

        $parts = parse_url($relativeUrl) ?: [];

        return $this->assembleUrl($this->addLocaleToHost($locale), $parts['path'] ?? '', $parts['query'] ?? null);
    }

    /**
     * The current URL with the given host and path.
     *
     * @param  string  $host  [like "de.example.com"]
     * @param  string|false  $path  [a path of the app, false keeps the path of the current request]
     * @param  string|null  $query
     * @return string
     */
    protected function assembleUrl($host, $path, $query = null)
    {
        // Retrieve the current URL components
        $parsed_url = Accent::parseCurrentUrl();

        $parsed_url['host'] = $host;

        if ($path !== false) {
            $parsed_url['path'] = $this->withBasePath($path, request()->getBaseUrl());
        }

        if ($query !== null) {
            $parsed_url['query'] = $query;
        }

        return $this->unparseUrlWithoutTrailingSlash($parsed_url);
    }

    /**
     * An app in a subfolder, like https://example.com/shop, keeps "/shop" before the path.
     *
     * @param  string  $path
     * @param  string  $baseUrl  [like "/shop", or "" for an app in the root]
     * @return string
     */
    protected function withBasePath($path, $baseUrl)
    {
        $path = trim($path, '/');

        return rtrim($baseUrl, '/').($path !== '' ? '/'.$path : '');
    }

    /**
     * The home page, like url('/'), has no trailing slash.
     *
     * @param  array  $parsed_url
     * @return string
     */
    protected function unparseUrlWithoutTrailingSlash(array $parsed_url)
    {
        if (isset($parsed_url['path']) && trim($parsed_url['path'], '/') === '') {
            unset($parsed_url['path']);
        }

        return Accent::unparseUrl($parsed_url);
    }

    /**
     * Makes a relative or a protocol-relative URL absolute.
     *
     * @param  string  $url
     * @return string
     */
    protected function absoluteUrl(string $url): string
    {
        // A protocol-relative URL, like //de.example.com/hallo, gets the scheme of the current request
        if (Str::startsWith($url, '//')) {
            return request()->getScheme().':'.$url;
        }

        // A relative URL is relative to the app, like url('hallo/john')
        return url()->to($url);
    }

    /**
     * Runs the callback with the given app locale and sets the previous locale again.
     *
     * @param  string  $locale
     * @param  callable  $callback
     * @return mixed
     */
    protected function inLocale($locale, callable $callback)
    {
        $previous = app()->getLocale();

        if ($previous === $locale) {
            return $callback();
        }

        app()->setLocale($locale);

        try {
            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * Finds the route for a GET request, like the router does, and binds it.
     * The app registered its translated routes only in the locale of the current request.
     * So a translated route gets the path of the locale of the URL for the comparison.
     * Route model binding gives the models for translated slugs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $locale  [the locale of the URL]
     * @return array|null [the bound copy of the route and the route of the app]
     */
    protected function findRouteByRequest(Request $request, $locale)
    {
        [$fallbacks, $routes] = collect(app('router')->getRoutes()->get('GET'))->partition(function ($route) {
            return $route->isFallback;
        });

        foreach ($routes->merge($fallbacks) as $route) {
            $candidate = $this->routeInLocale($route, $locale);

            if (! $candidate->matches($request)) {
                continue;
            }

            // A copy, so the route of the current request keeps its parameters
            $candidate = $candidate === $route ? clone $route : $candidate;
            $candidate->bind($request);

            try {
                app('router')->substituteBindings($candidate);
                app('router')->substituteImplicitBindings($candidate);
            } catch (Exception $e) {
                // For example a deleted model. The URL keeps the values that it has.
            }

            return [$candidate, $route];
        }

        return null;
    }

    /**
     * A copy of a translated route with its path in the given locale, or the route itself.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $locale
     * @return \Illuminate\Routing\Route
     */
    protected function routeInLocale(Route $route, $locale)
    {
        $translationKey = $this->interpretedKey($route);

        // Only a path from interpret() differs between the locales
        $path = $translationKey !== false
            ? $this->withPrefix($this->routePrefix($route), Accent::findRoutePathByName($translationKey, $locale))
            : false;

        if ($path === false || $this->normalizePath($path) === $this->normalizePath($route->uri())) {
            return $route;
        }

        $copy = clone $route;
        $copy->compiled = null;

        return $copy->setUri(trim($path, '/') ?: '/');
    }

    /**
     * The translation key that interpret() gave the path of the route. Two routes can have
     * the same path, like GET and POST "contact". Then the route name decides, if it is one of the keys.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string|false
     */
    protected function interpretedKey(Route $route)
    {
        $path = $this->normalizePath($route->uri());
        $keys = [];

        foreach ($this->interpretedRoutes as $interpreted) {
            if ($interpreted['path'] === $path) {
                $keys[] = $interpreted['key'];
            }
        }

        if (! $keys) {
            return false;
        }

        return in_array($route->getName(), $keys, true) ? $route->getName() : $keys[0];
    }

    /**
     * The translated path of a route from interpret(), with the prefix of its route group.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $locale
     * @return string|false
     */
    protected function translatedRoutePath(Route $route, $locale)
    {
        $translationKey = $this->findRouteNameByPath($route->uri());

        if ($translationKey === false) {
            return false;
        }

        return $this->withPrefix($this->routePrefix($route), Accent::findRoutePathByName($translationKey, $locale));
    }

    /**
     * The translated path of a translation key. The path gets the prefix of the given route.
     * Without a route, it gets the prefix of the first route from interpret() with this key.
     *
     * @param  string|false|null  $translationKey
     * @param  string  $locale
     * @param  \Illuminate\Routing\Route|null  $route
     * @return string|false
     */
    protected function translatedKeyPath($translationKey, $locale, ?Route $route = null)
    {
        $path = Accent::findRoutePathByName($translationKey, $locale);

        if ($path === false) {
            return false;
        }

        if ($route) {
            return $this->withPrefix($this->routePrefix($route), $path);
        }

        foreach ($this->interpretedRoutes as $interpreted) {
            if ($interpreted['key'] === $translationKey) {
                return $this->withPrefix($interpreted['prefix'], $path);
            }
        }

        return $path;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     * @return string [like "admin" for a route in Route::prefix('admin')->group()]
     */
    protected function routePrefix(Route $route)
    {
        return trim((string) $route->getPrefix(), '/');
    }

    /**
     * @param  string  $prefix
     * @param  string|false  $path
     * @return string|false
     */
    protected function withPrefix($prefix, $path)
    {
        if ($path === false || $prefix === '') {
            return $path;
        }

        return $prefix.'/'.ltrim($path, '/');
    }

    /**
     * @param  string|false  $routeName
     * @return \Illuminate\Routing\Route|null
     */
    protected function findRouteByName($routeName)
    {
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        return app('router')->getRoutes()->getByName($routeName);
    }

    /**
     * If we have beautify on and the given $locale is the same
     * to the current locale and to the fallbackLocal.
     * We don't need to add a subdomain to the host.
     *
     * @param  string  $locale
     * @return string
     */
    protected function addLocaleToHost($locale)
    {
        if (Config::beautify() && $locale === tongue()->current() && $locale === Config::fallbackLocale()) {
            return Url::domain();
        }

        // Add locale to the host
        return Url::localeSubdomain($locale).'.'.Url::domain();
    }

    /**
     * Interprets a translated route path for the given route name.
     *
     * @param $routeName
     * @return string|false (but should be string if it exists!)
     */
    public function interpret($routeName)
    {
        $routePath = Accent::findRoutePathByName($routeName);

        if ($routePath !== false) {
            // Inside Route::prefix('admin')->group() the route path starts with "admin"
            $prefix = trim(app('router')->getLastGroupPrefix(), '/');
            $interpreted = ['path' => $this->normalizePath($prefix.'/'.$routePath), 'key' => $routeName, 'prefix' => $prefix];

            if (! in_array($interpreted, $this->interpretedRoutes, true)) {
                $this->interpretedRoutes[] = $interpreted;
            }
        }

        return $routePath;
    }

    /**
     * Find the route name matching the given route path.
     * The route path is like Route::uri(): with the group prefix, without slashes at the ends.
     *
     * @param  string  $routePath
     * @return bool|string [the translation key that interpret() got]
     */
    public function findRouteNameByPath($routePath)
    {
        $routePath = $this->normalizePath($routePath);

        // Two routes can have the same path, like GET and POST "contact". The first one wins.
        foreach ($this->interpretedRoutes as $interpreted) {
            if ($interpreted['path'] === $routePath) {
                return $interpreted['key'];
            }
        }

        return false;
    }

    /**
     * Like Laravel stores a route path: "/admin//{post:slug}/" becomes "admin/{post}".
     *
     * @param  string  $path
     * @return string
     */
    protected function normalizePath($path)
    {
        $path = preg_replace('/\{(\w+):\w+(\??)\}/', '{$1$2}', (string) $path);

        return trim(preg_replace('#/+#', '/', $path), '/');
    }

    /**
     * Redirect back to the latest locale.
     * Used, when no language is found.
     *
     * @return \Illuminate\Http\RedirectResponse;
     */
    public function redirectBackToLatest()
    {
        tongue()->speaks(Localization::currentTongue());

        return dialect()->redirect(dialect()->redirectUrl());
    }
}
