<?php

namespace Pmochine\LaravelTongue\Misc;

use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Domain;
use Pdp\Manager;

/**
 * Small wrapper for getting the getRegistrableDomain.
 */
class DomainParser
{
    /** @var Pdp\Rules */
    protected $rules;

    public function __construct()
    {
        $manager = new Manager(new Cache(), new CurlHttpClient());
        $this->rules = $manager->getRules();
    }

    /**
     * https://github.com/jeremykendall/php-domain-parser.
     *
     * @param   string  $url
     *
     * @return  Domain
     */
    public function resolve(string $url): Domain
    {
        return $this->rules->resolve($url);
    }
}
