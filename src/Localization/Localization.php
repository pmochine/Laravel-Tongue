<?php

namespace Pmochine\LaravelTongue\Localization;

use Illuminate\Support\Arr;
use Pmochine\LaravelTongue\Misc\Config;
use Illuminate\Contracts\Encryption\DecryptException;

class Localization
{
    /** Name of the cookie */
    const COOKIE = 'tongue-locale';

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
            if (!Config::beautify()) {
                // if the middleware is active we should be redirected to en.domain.com
                // if not the fallback language is going to be used
                return Config::fallbackLocale();
            }
            // we are checking if we have languages set in cookies or in the browser
            return self::currentTongue();
        }

        // could be a custom subdomain
        if (!tongue()->isSpeaking($locale)) {
            // check if it is a white listed domain
            if (tongue()->speaking('subdomains', $locale)) {
                return self::currentTongue();
            }
            // check if we have a custom locale subdomain, if not it returns a null
            $locale = tongue()->speaking('aliases', $locale);
        }

        return $locale;
    }

    /**
     * Gets the locale from the url.
     * @return string|bool false [when hostname===locale]
     */
    public static function fromUrl()
    {
        $hostname = explode('.', self::domain())[0];
        $locale = explode('.', request()->getHost())[0];

        return  $hostname === $locale ? false : $locale;
    }

    /**
     * Gets the registrable Domain of the website from the config.
     * If not set we are going to get it with TLDExtract.
     * @return string
     */
    public static function domain(): string
    {
        if ($domain = Config::domain()) {
            return $domain;
        }

        return self::extractDomain();
    }

    /**
     * Gets the registrable Domain of the website.
     *
     * https://github.com/layershifter/TLDExtract
     *
     * @return  string
     */
    protected static function extractDomain(): string
    {
        $extract = new \LayerShifter\TLDExtract\Extract();

        $result = $extract->parse(request()->getHost());

        return $result->getRegistrableDomain();
    }

    /**
     * Tries to get the current locale of the user.
     * Via the Browser or the fallback language.
     *
     * @return string
     */
    protected static function currentTongue()
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
    protected static function languageIsSet()
    {
        return !app()->runningInConsole() || Arr::has(request()->server(), 'HTTP_ACCEPT_LANGUAGE');
    }

    /**
     * Sets or gets the cookie of the locale.
     * Important to set config/session domain to .exmaple.com
     * https://gistlog.co/JacobBennett/15558410de2a394373ac.
     *
     * @param  string $locale
     * @return string|null
     */
    public static function cookie($locale = null)
    {
        if ($locale != null) {
            return cookie()->queue(cookie()->forever(self::COOKIE, $locale));
        }

        if (!request()->hasCookie(self::COOKIE)) {
            return;
        }

        try {
            //Somehow I got this error: unserialize(): Error at offset 0 of 2 bytes
            //I needed to change decrypt(value, unserialize = false);
            return app('encrypter')->decrypt(request()->cookie(self::COOKIE), false);
        } catch (DecryptException $e) {
            //Somehow the middleware for decrypting does not kick in here...
            //but it even fails if we use php artisan <something> (weird)
            //if it happes we can simply give it normally back
            return request()->cookie(self::COOKIE);
        } catch (Exception $e) {
            //So I don't return a cookie in that case
            return;
        }
    }
}
