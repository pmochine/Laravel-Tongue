<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Http\RedirectResponse;
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
     * An array that contains all routes that should be translated.
     *
     * @var array [translation key => path in the locale of interpret()]
     */
    protected $translatedRoutes = [];

    /**
     * The prefix of the route group, in which interpret() was called.
     *
     * @var array [translation key => prefix, like "admin"]
     */
    protected $routePrefixes = [];

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
        return $this->translate($this->currentRouteName(), Accent::currentRouteAttributes(), $locale);
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

        // Retrieve the current URL components
        $parsed_url = Accent::parseCurrentUrl();

        $parsed_url['host'] = $this->addLocaleToHost($locale);

        // Resolve the translated route path for the given translation key, like "routes.welcome"
        $path = $this->translatedPath($routeName, $locale);
        $bindingFields = [];

        // Or for the name of a route, like "welcome"
        if ($path === false && $route = $this->findRouteByName($routeName)) {
            // The path of the route could come from interpret(). Then we translate it.
            $translationKey = $this->findRouteNameByPath($route->uri());
            $path = $translationKey !== false ? $this->translatedPath($translationKey, $locale) : false;
            $path = $path !== false ? $path : $route->uri();
            $bindingFields = $route->bindingFields();
        }

        if ($path !== false) {
            $parsed_url['path'] = $path;
        }

        if (isset($parsed_url['path'])) {
            // Substitute the attributes and remove the missing optional ones
            $parsed_url['path'] = Accent::substituteAttributesInRoute($routeAttributes ?: [], $parsed_url['path'], $bindingFields);

            // The home page, like url('/'), has no trailing slash
            if (trim($parsed_url['path'], '/') === '') {
                unset($parsed_url['path']);
            }
        }

        return Accent::unparseUrl($parsed_url);
    }

    /**
     * The path of a translation key in the given locale, with the prefix of its route group.
     *
     * @param  string|false  $translationKey
     * @param  string  $locale
     * @return string|false
     */
    protected function translatedPath($translationKey, $locale)
    {
        $path = Accent::findRoutePathByName($translationKey, $locale);

        if ($path === false || empty($this->routePrefixes[$translationKey])) {
            return $path;
        }

        return $this->routePrefixes[$translationKey].'/'.ltrim($path, '/');
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

        if (! isset($this->translatedRoutes[$routeName])) {
            $this->translatedRoutes[$routeName] = $routePath;
            // Inside Route::prefix('admin')->group() the route path starts with "admin"
            $this->routePrefixes[$routeName] = trim(app('router')->getLastGroupPrefix(), '/');
        }

        return $routePath;
    }

    /**
     * Get the current route name.
     *
     * @return bool|string
     */
    protected function currentRouteName()
    {
        if (app('router')->currentRouteName()) {
            return app('router')->currentRouteName();
        }

        if (app('router')->current()) {
            return $this->findRouteNameByPath(app('router')->current()->uri());
        }

        return false;
    }

    /**
     * Find the route name matching the given route path.
     * The route path is like Route::uri(): with the group prefix, without slashes at the ends.
     *
     * @param  string  $routePath
     * @return bool|string
     */
    public function findRouteNameByPath($routePath)
    {
        $routePath = $this->normalizePath($routePath);

        foreach ($this->translatedRoutes as $name => $path) {
            if ($path === false) {
                continue;
            }

            if ($routePath === $this->normalizePath(($this->routePrefixes[$name] ?? '').'/'.$path)) {
                return $name;
            }
        }

        return false;
    }

    /**
     * Like Laravel stores a route path: "/admin/{post:slug}/" becomes "admin/{post}".
     *
     * @param  string  $path
     * @return string
     */
    protected function normalizePath($path)
    {
        return trim(preg_replace('/\{(\w+):\w+(\??)\}/', '{$1$2}', (string) $path), '/');
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
