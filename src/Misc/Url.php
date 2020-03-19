<?php

namespace Pmochine\LaravelTongue\Misc;

use Illuminate\Support\Str;

class Url
{
    public static function domain(): string
    {
        //check if the hosted domain is the same, if not use it from get host
        if (self::configDomainIsSet()) {
            return Config::domain();
        }

        return self::extractDomain();
    }

    /**
     * This is actually not that important. Only if you have
     * complicated domains like '155ad73e.eu.ngrok.io', whe I just
     * cannot tell what the real domain is.
     *
     * It is true when e.g.: '155ad73e.eu.ngrok.io' contains in 'yoursubdomain.155ad73e.eu.ngrok.io'
     *
     * @return  bool
     */
    protected static function configDomainIsSet(): bool
    {
        if (! $domain = Config::domain()) {
            return false;
        } // config was not set

        //the host could have a different domain, thats why we check it here
        return Str::contains(self::host(), $domain);
    }

    /**
     * Gets the registrable Domain of the website.
     *
     * https://github.com/jeremykendall/php-domain-parser
     *
     * @return  string
     */
    protected static function extractDomain(): string
    {
        $result = (new DomainParser)->resolve(self::host());

        return $result->getRegistrableDomain() ?: '';
    }

    public static function domainName(): string
    {
        $TLD = substr(self::domain(), strrpos(self::domain(), '.'));

        return Str::replaceLast($TLD, '', self::domain());
    }

    /**
     * @return  string  [like "de.domain.com"]
     */
    public static function host(): string
    {
        return request()->getHost();
    }

    /**
     * @return  string  [like "de" or when no subdomain "domain" of "domain.com"]
     */
    public static function subdomain(): string
    {
        return explode('.', self::host())[0];
    }

    public static function hasSubdomain(): bool
    {
        return explode('.', self::domain())[0] !== self::subdomain();
    }
}
