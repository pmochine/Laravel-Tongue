<?php

namespace Pmochine\LaravelTongue\Accent;

use BackedEnum;
use Illuminate\Contracts\Routing\UrlRoutable;
use Pmochine\LaravelTongue\Misc\Url;
use Stringable;

class Accent
{
    /**
     * Get url using array data from parse_url.
     *
     * @param  array|false  $parsed_url  Array of data from parse_url function
     * @return string Returns URL as string.
     */
    public static function unparseUrl($parsed_url)
    {
        if (empty($parsed_url)) {
            return '';
        }
        $url = '';
        $url .= isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $url .= isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $url .= isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $url .= $user.(($user || $pass) ? "$pass@" : '');
        if (! empty($url)) {
            $url .= isset($parsed_url['path']) ? '/'.ltrim($parsed_url['path'], '/') : '';
        } elseif (empty($url)) {
            $url .= isset($parsed_url['path']) ? $parsed_url['path'] : '';
        }
        $url .= isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
        $url .= isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';

        return $url;
    }

    /**
     * Get the parameters of the current route, as they are in the URL.
     * Route model binding has not replaced them with models yet.
     *
     * @return bool|array
     */
    public static function currentRouteAttributes()
    {
        if (app('router')->current()) {
            return array_filter(app('router')->current()->originalParameters(), function ($value) {
                return ! is_null($value);
            });
        }

        return false;
    }

    /**
     * Find the route path matching the given route name.
     * Important: Translator can give you an array as well.
     *
     * @param  string  $routeName
     * @param  string|null  $locale
     * @return string|false
     */
    public static function findRoutePathByName($routeName, $locale = null)
    {
        if (app('translator')->has($routeName, $locale)) {
            $name = app('translator')->get($routeName, [], $locale);

            return is_string($name) ? $name : false;
        }

        return false;
    }

    /**
     * Change route attributes for the ones in the $attributes array.
     *
     * @param  array  $attributes  Array of attributes
     * @param  string  $route  route to substitute
     * @return string route with attributes changed
     */
    public static function substituteAttributesInRoute($attributes, $route)
    {
        foreach ($attributes as $key => $value) {
            if ($value instanceof UrlRoutable) {
                $value = $value->getRouteKey();
            } elseif ($value instanceof BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof Stringable) {
                $value = (string) $value;
            }

            // Route::view() and route defaults can hold arrays. They are no part of the path.
            if (! is_scalar($value)) {
                continue;
            }

            $route = str_replace(['{'.$key.'}', '{'.$key.'?}'], (string) $value, $route);
        }

        // delete empty optional arguments that are not in the $attributes array
        $route = preg_replace('/\/\{[^\/}]+\?\}/', '', $route);

        return $route;
    }

    /**
     * Stores the parsed url array after a few modifications.
     *
     * @return array
     */
    public static function parseCurrentUrl()
    {
        $parsed_url = parse_url(app()['request']->fullUrl());

        // Don't store path, query and fragment
        unset($parsed_url['query']);
        unset($parsed_url['fragment']);

        $parsed_url['host'] = Url::domain();

        return $parsed_url;
    }
}
