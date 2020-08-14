<?php

namespace Pmochine\LaravelTongue\Misc;

class Config
{
    public static function domain()
    {
        return config('localization.domain');
    }

    public static function subdomains()
    {
        return config('localization.subdomains', []);
    }

    public static function aliases()
    {
        return config('localization.aliases', []);
    }

    public static function beautify()
    {
        return config('localization.beautify_url');
    }

    public static function fallbackLocale()
    {
        return config('app.fallback_locale');
    }

    public static function supportedLocales()
    {
        return config('localization.supportedLocales');
    }

    public static function acceptLanguage()
    {
        return config('localization.acceptLanguage');
    }

    public static function cookieLocalization()
    {
        return config('localization.cookie_localization');
    }

    public static function cookieSerialize()
    {
        return config('localization.cookie_serialize', false);
    }

    public static function preventRedirect()
    {
        return config('localization.prevent_redirect', false);
    }
}
