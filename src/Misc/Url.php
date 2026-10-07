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
     * @return bool
     */
    protected static function configDomainIsSet(): bool
    {
        if (!$domain = Config::domain()) {
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
     * @return string
     */
    protected static function extractDomain(): string
    {
        $result = (new DomainParser())->resolve(self::host());

        return $result->registrableDomain()->toString() ?: '';
    }

    public static function domainName(): string
    {
        $TLD = substr(self::domain(), strrpos(self::domain(), '.'));

        return Str::replaceLast($TLD, '', self::domain());
    }

    /**
     * @return string [like "de.domain.com"]
     */
    public static function host(): string
    {
        return request()->getHost();
    }

    /**
     * @return string [like "de" or when no subdomain "domain" of "domain.com"]
     */
    public static function subdomain(): string
    {
        return explode('.', self::host())[0];
    }

    /**
     * With the option "alias_urls" a locale uses its first alias as subdomain.
     *
     * @return string [like "de" or its alias "gewinnen"]
     */
    public static function localeSubdomain(string $locale): string
    {
        // The beautiful URL of the fallback locale wins over its alias. The link keeps the locale,
        // so the cookie switches to the fallback locale before the middleware redirects.
        if (!Config::aliasUrls() || (Config::beautify() && $locale === Config::fallbackLocale())) {
            return $locale;
        }

        foreach (Config::aliases() as $alias => $aliasLocale) {
            // Hosts are lowercase, and PHP stores an alias like "123" as integer key
            $alias = strtolower((string) $alias);

            // The detection ignores an alias that is a locale or a whitelisted subdomain, and it takes
            // the first alias that matches. So a URL uses only an alias that leads back to the locale.
            if ($aliasLocale === $locale && !self::isReservedSubdomain($alias)
                && tongue()->speaking('aliases', $alias) === $locale) {
                return $alias;
            }
        }

        return $locale;
    }

    /**
     * A locale or a whitelisted subdomain cannot be an alias.
     */
    protected static function isReservedSubdomain(string $subdomain): bool
    {
        $reserved = array_merge(array_keys(Config::supportedLocales() ?: []), Config::subdomains());

        return in_array(strtolower($subdomain), array_map('strtolower', $reserved), true);
    }

    /**
     * The locale of a host of the app, like "de" for de.domain.com or for its alias gewinnen.domain.com.
     *
     * @return string|null [null for the bare domain, a whitelisted subdomain or another domain]
     */
    public static function localeOfHost(string $host): ?string
    {
        $host = strtolower($host);

        if (!Str::endsWith($host, '.'.self::domain())) {
            return null;
        }

        $subdomain = explode('.', $host)[0];

        if (tongue()->isSpeaking($subdomain)) {
            return $subdomain;
        }

        // Like Localization::decipherTongue(): a whitelisted subdomain is never an alias
        if (tongue()->speaking('subdomains', $subdomain)) {
            return null;
        }

        $locale = tongue()->speaking('aliases', $subdomain);

        return is_string($locale) && tongue()->isSpeaking($locale) ? $locale : null;
    }

    public static function hasSubdomain(): bool
    {
        // Compare the whole domain: "example.example.com" has the subdomain "example".
        return Str::endsWith(self::host(), '.'.self::domain());
    }
}
