<?php

namespace Pmochine\LaravelTongue\Misc;

use LayerShifter\TLDExtract\Extract;

class Url
{
    public static function domain(): string
    {
        //maybe check if the hosted domain is the same, if not use it from get host
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
        $result = (new Extract())->parse(self::host());

        return $result->getRegistrableDomain();
    }

    public static function domainName(): string
    {
        return explode('.', self::domain())[0];
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
        return self::domainName() !== self::subdomain();
    }
}
