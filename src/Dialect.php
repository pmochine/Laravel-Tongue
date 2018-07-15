<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Application;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Accent\Accent;
use Pmochine\LaravelTongue\Localization\Localization;

/**
 * This class was written by
 * https://github.com/hoyvoy/laravel-subdomain-localization
 * Now is the time to have my own dialect with it :P.
 */
class Dialect
{
    /**
     * Our instance of the Laravel app.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app = '';

    /**
     * An array that contains information about the current request.
     *
     * @var array
     */
    protected $parsed_url;

    /**
     * An array that contains all routes that should be translated.
     *
     * @var array
     */
    protected $translatedRoutes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Adds the detected locale to the current unlocalized URL.
     *
     * @return string
     */
    public function redirectUrl($url = null)
    {
        $parsed_url = parse_url($url ?? request()->fullUrl());

        $domain = Localization::domain();

        if (Config::beautify() && Localization::fromUrl() === Config::fallbackLocale()) {
            $parsed_url['host'] = $domain;
        } else {
            $parsed_url['host'] = tongue()->current().'.'.$domain;
        }

        return Accent::unparseUrl($parsed_url);
    }

    /**
     * Creates the redirect response.
     *
     * @param  string
     * @return Illuminate\Http\RedirectResponse;
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
     *
     * @return bool|string
     */
    public function current($locale)
    {
        return $this->translate($this->currentRouteName(), Accent::currentRouteAttributes(), $locale);
    }

    /**
     * Get all Translations for the current URL.
     *
     * @param bool $excludeCurrentLocale
     *
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
     * @param string       $routeName
     * @param string|false $routeAttributes
     * @param string|false $locale
     *
     * @return string|bool
     */
    public function translate($routeName, $routeAttributes = null, $locale = null)
    {
        // If no locale is given, we use the current locale
        if (! $locale) {
            $locale = tongue()->current();
        }

        if (! $this->parsed_url) {
            $this->parsed_url = Accent::parseCurrentUrl();
        }

        // Retrieve the current URL components
        $parsed_url = $this->parsed_url;

        // Add locale to the host
        $parsed_url['host'] = $locale.'.'.Localization::domain();

        // Resolve the translated route path for the given route name
        $translatedPath = Accent::findRoutePathByName($routeName, $locale);

        if ($translatedPath !== false) {
            $parsed_url['path'] = $translatedPath;
        }

        // If attributes are given, substitute them in the path
        if ($routeAttributes) {
            $parsed_url['path'] = Accent::substituteAttributesInRoute($routeAttributes, $parsed_url['path']);
        }

        return Accent::unparseUrl($parsed_url);
    }

    /**
     * Interprets a translated route path for the given route name.
     *
     * @param $routeName
     *
     * @return string
     */
    public function interpret($routeName)
    {
        $routePath = Accent::findRoutePathByName($routeName);

        if (! isset($this->translatedRoutes[$routeName])) {
            $this->translatedRoutes[$routeName] = $routePath;
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
     *
     * @param string $routePath
     *
     * @return bool|string
     */
    public function findRouteNameByPath($routePath)
    {
        foreach ($this->translatedRoutes as $name => $path) {
            if ($routePath == $path) {
                return $name;
            }
        }

        return false;
    }
}
