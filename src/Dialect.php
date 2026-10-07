<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Pmochine\LaravelTongue\Accent\Accent;
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
     * The routes from interpret(), by their path like Route::uri() stores it.
     *
     * @var array [path, like "admin/hello/{user}" => ['key' => translation key, 'prefix' => prefix of the route group]]
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
        $path = false;

        if ($route) {
            $path = $this->translatedRoutePath($route, $locale);

            // A route name that is a translation key, like "routes.welcome"
            if ($path === false && is_string($route->getName())) {
                $path = $this->translatedKeyPath($route->getName(), $locale);
            }
        }

        // Without a translation, the URL keeps the path of the current request.
        return $this->buildUrl($locale, $path, Accent::currentRouteAttributes() ?: []);
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

        $attributes = is_iterable($routeAttributes) ? collect($routeAttributes)->all() : [];
        $route = $this->findRouteByName($routeName);
        $bindingFields = $route ? $route->bindingFields() : [];

        // A route with a path from interpret()
        $path = $route ? $this->translatedRoutePath($route, $locale) : false;

        // A translation key, like "routes.welcome"
        if ($path === false) {
            $path = $this->translatedKeyPath($routeName, $locale);
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

        return $this->assembleUrl($locale, $path);
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

        return $this->assembleUrl($locale, $parts['path'] ?? '', $parts['query'] ?? null);
    }

    /**
     * The current URL with the host of the locale and the given path.
     *
     * @param  string  $locale
     * @param  string|false  $path  [false keeps the path of the current request]
     * @param  string|null  $query
     * @return string
     */
    protected function assembleUrl($locale, $path, $query = null)
    {
        // Retrieve the current URL components
        $parsed_url = Accent::parseCurrentUrl();

        $parsed_url['host'] = $this->addLocaleToHost($locale);

        if ($path !== false) {
            $parsed_url['path'] = $path;
        }

        if ($query !== null) {
            $parsed_url['query'] = $query;
        }

        // The home page, like url('/'), has no trailing slash
        if (isset($parsed_url['path']) && trim($parsed_url['path'], '/') === '') {
            unset($parsed_url['path']);
        }

        return Accent::unparseUrl($parsed_url);
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
        $interpreted = $this->interpretedRoutes[$this->normalizePath($route->uri())] ?? null;

        if (! $interpreted) {
            return false;
        }

        return $this->withPrefix($interpreted['prefix'], Accent::findRoutePathByName($interpreted['key'], $locale));
    }

    /**
     * The translated path of a translation key. If interpret() used the key in a
     * route group with a prefix, the path gets the prefix of the first route.
     *
     * @param  string|false  $translationKey
     * @param  string  $locale
     * @return string|false
     */
    protected function translatedKeyPath($translationKey, $locale)
    {
        $path = Accent::findRoutePathByName($translationKey, $locale);

        foreach ($this->interpretedRoutes as $interpreted) {
            if ($interpreted['key'] === $translationKey) {
                return $this->withPrefix($interpreted['prefix'], $path);
            }
        }

        return $path;
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
            $path = $this->normalizePath($prefix.'/'.$routePath);

            if (! isset($this->interpretedRoutes[$path])) {
                $this->interpretedRoutes[$path] = ['key' => $routeName, 'prefix' => $prefix];
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
        return $this->interpretedRoutes[$this->normalizePath($routePath)]['key'] ?? false;
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
