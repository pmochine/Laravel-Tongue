<?php

namespace Pmochine\LaravelTongue\Accent;

use BackedEnum;
use Illuminate\Contracts\Routing\UrlRoutable;
use Pmochine\LaravelTongue\Misc\Url;
use Stringable;

class Accent
{
    /**
     * Characters that stay unencoded in a route attribute, like in route() of Laravel.
     * Unlike route(), "#", "?" and "%" are encoded, so the URL keeps the value.
     */
    protected const DONT_ENCODE = [
        '%2F' => '/', '%40' => '@', '%3A' => ':', '%3B' => ';', '%2C' => ',', '%3D' => '=',
        '%2B' => '+', '%21' => '!', '%2A' => '*', '%7C' => '|', '%26' => '&',
    ];

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
        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        if (app('translator')->has($routeName, $locale)) {
            $name = app('translator')->get($routeName, [], $locale);

            return is_string($name) ? $name : false;
        }

        return false;
    }

    /**
     * Change route attributes for the ones in the $attributes array.
     * A missing optional attribute disappears with its slash or dot, like in "files/{name}.{extension?}".
     * A missing required attribute stays.
     *
     * @param  array  $attributes  Array of attributes
     * @param  string  $route  route to substitute
     * @param  array  $bindingFields  like ['post' => 'slug'] for the placeholder {post}
     * @return string route with attributes changed
     */
    public static function substituteAttributesInRoute($attributes, $route, array $bindingFields = [])
    {
        return preg_replace_callback('/([\/.]?)\{(\w+)(?::(\w+))?(\??)\}/', function ($match) use ($attributes, $bindingFields) {
            $name = $match[2];
            $field = $match[3] !== '' ? $match[3] : ($bindingFields[$name] ?? null);
            $value = self::routeValue($attributes[$name] ?? null, $field);

            if ($value !== null) {
                return $match[1].$value;
            }

            return $match[4] === '?' ? '' : $match[0];
        }, $route);
    }

    /**
     * The value of a route attribute, encoded for the path.
     *
     * @param  mixed  $value
     * @param  string|null  $field  [the binding field, like "slug" in {post:slug}]
     * @return string|null [null if the value cannot be part of the path]
     */
    protected static function routeValue($value, ?string $field): ?string
    {
        if ($value instanceof UrlRoutable) {
            $value = $field ? $value->{$field} : $value->getRouteKey();
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof Stringable) {
            $value = (string) $value;
        }

        // Route::view() and route defaults can hold arrays. They are no part of the path.
        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        return strtr(rawurlencode((string) $value), self::DONT_ENCODE);
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
