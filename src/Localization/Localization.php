<?php

namespace Pmochine\LaravelTongue\Localization;

use Illuminate\Support\Arr;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Misc\Cookie;
use Pmochine\LaravelTongue\Misc\Url;

class Localization
{
    /**
     * The goal is to find the language (the locale).
     * We look at the subdomain or in the cookies or browser language.
     *
     * @return string [Finds the locale of the url]
     */
    public static function decipherTongue()
    {
        $locale = self::fromUrl();

        // there is no subdomain found
        if ($locale === false) {
            // this could be a future bug
            // when no middleware is active the language is not set right
            // domain.com could be in german etc...
            if (! Config::beautify()) {
                // if the middleware is active we should be redirected to en.domain.com
                // if not the fallback language is going to be used
                return Config::fallbackLocale();
            }
            // we are checking if we have languages set in cookies or in the browser
            return self::currentTongue();
        }

        // could be a custom subdomain
        if (! tongue()->isSpeaking($locale)) {
            // check if it is a white listed domain

            if (tongue()->speaking('subdomains', $locale)) {
                return self::currentTongue();
            }

            // check if we have a custom locale subdomain, if not it returns a null
            $locale = tongue()->speaking('aliases', $locale) ?: $locale;
        }

        return $locale;
    }

    /**
     * Gets the locale from the url.
     * @return string|bool false [when hostname===locale]
     */
    public static function fromUrl()
    {
        return  Url::hasSubdomain() ? Url::subdomain() : false;
    }

    /**
     * Tries to get the current locale of the user.
     * Via the Browser or the fallback language.
     *
     * @return string
     */
    public static function currentTongue(): string
    {
        if (Config::cookieLocalization() && $locale = self::cookie()) {
            return $locale;
        }

        if (Config::acceptLanguage() && self::languageIsSet()) {
            $detector = new TongueDetector(Config::fallbackLocale(), Config::supportedLocales(), request());

            return $detector->negotiateLanguage();
        }

        return Config::fallbackLocale();
    }

    /**
     * Only for testing the TongueDetector.
     *
     * @return bool
     */
    protected static function languageIsSet(): bool
    {
        return ! app()->runningInConsole() || Arr::has(request()->server(), 'HTTP_ACCEPT_LANGUAGE');
    }

    /**
     * Sets or gets the cookie of the locale.
     * Important to set config/session domain to .exmaple.com
     * https://gistlog.co/JacobBennett/15558410de2a394373ac.
     *
     * @param  string $locale
     * @return string|null
     */
    public static function cookie(string $locale = null): ?string
    {
        $cookie = new Cookie('tongue-locale', Config::cookieSerialize()); //Name of the cookie

        if ($locale !== null) {
            $cookie->save($locale);

            return null;
        }

        return $cookie->get();
    }
}
