<?php

namespace Pmochine\LaravelTongue\Misc;

use Pdp\ResolvedDomain;

/**
 * Small wrapper for getting the getRegistrableDomain.
 */
class DomainParser
{
    /**
     * https://github.com/jeremykendall/php-domain-parser.
     * https://github.com/kevindierkx/laravel-domain-parser.
     *
     * @param  string  $url
     * @return Domain
     */
    public function resolve(string $url): ResolvedDomain
    {
        return \TopLevelDomains::resolve($url);
    }
}
