<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Foundation\Application;
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
     * Our instance of the Laravel app.
     *
     * @var \Illuminate\Foundation\Application
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
    public function redirectUrl($url = null, string $locale = null)
    {
        $parsed_url = parse_url($url ?? request()->fullUrl());

        $domain = Url::domain();

        if (Config::beautify() && Localization::fromUrl() === Config::fallbackLocale()) {
            $parsed_url['host'] = $domain;
        } else {
            $parsed_url['host'] = tongue()->current().'.'.$domain;
        }

        if ($locale) {
            $parsed_url['host'] = $locale.'.'.$domain;
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
     * @param array]null]bool $routeAttributes
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

        if (empty($this->parsed_url)) {
            $this->parsed_url = Accent::parseCurrentUrl();
        }

        // Retrieve the current URL components
        $parsed_url = $this->parsed_url;

        $parsed_url['host'] = $this->addLocaleToHost($locale);

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
     * If we have beautify on and the given $locale is the same
     * to the current locale and to the fallbackLocal.
     * We don't need to add a subdomain to the host.
     *
     * @param string $locale
     * @return string
     */
    protected function addLocaleToHost($locale)
    {
        if (Config::beautify() && $locale === tongue()->current() && $locale === Config::fallbackLocale()) {
            return Url::domain();
        }

        // Add locale to the host
        return $locale.'.'.Url::domain();
    }

    /**
     * Interprets a translated route path for the given route name.
     *
     * @param $routeName
     *
     * @return string|false (but should be string if it exists!)
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
