<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Misc\Url;
use Pmochine\LaravelTongue\ServiceProvider;
use Pmochine\LaravelTongue\Tests\TestCase;

/**
 * First we check it with a simple domain (like laraveltongue.dev)
 * In the second part a complicated domain like '155ad73e.eu.ngrok.io'.
 */
class UrlTest extends TestCase
{
    protected $pathLocalized = 'localized';
    protected $pathNotLocalized = 'not-localized';
    protected $longDomain = '155ad73e.eu.ngrok.io';

    /**
     * Get package providers. To read the config file.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /**
     * First part with a simple domain
     * No need to set env APP_DOMAIN.
     */

    /** @test */
    public function it_returns_full_domain_of_request_host()
    {
        $this->sendingRequest(); //to get host() to work

        $this->assertEquals($this->domain, Url::domain());
    }

    /** @test */
    public function it_returns_domain_name()
    {
        $this->sendingRequest(); //to get host() to work

        $this->assertEquals(explode('.', $this->domain)[0], Url::domainName());
    }

    /** @test */
    public function it_returns_full_host()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals('de.'.$this->domain, Url::host());
    }

    /** @test */
    public function it_returns_subdomain()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals('de', Url::subdomain());
    }

    /** @test */
    public function it_checks_if_it_has_subdomain()
    {
        $this->sendingRequest(); //to get host() to work

        $this->assertFalse(Url::hasSubdomain());

        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertTrue(Url::hasSubdomain());
    }

    /**
     * Second part:
     * Now with a complicated domain.
     */

    /** @test */
    public function it_returns_full_long_domain_from_env()
    {
        $this->setLongDomain();

        $this->sendingRequest(); //to get host() to work

        $this->assertEquals($this->longDomain, Config::domain());
    }

    /** @test */
    public function it_returns_long_domain_name()
    {
        $this->setLongDomain();

        $this->sendRequest('GET', $this->pathLocalized, 'de'); // we send the request

        $this->assertEquals('155ad73e.eu.ngrok', Url::domainName());
    }

    /** @test */
    public function it_returns_subdomain_with_long_domain_name()
    {
        $this->setLongDomain();

        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals('155ad73e.eu.ngrok', Url::domainName());

        $this->assertEquals('de', Url::subdomain());
    }

    /** @test */
    public function it_checks_if_it_has_subdomain_with_long_domain_name()
    {
        $this->setLongDomain();

        $this->sendRequest('GET', $this->pathLocalized);

        $this->assertFalse(Url::hasSubdomain());

        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertTrue(Url::hasSubdomain());
    }

    protected function sendingRequest()
    {
        // we need to send a request to get the domain
        $this->sendRequest('GET', $this->pathNotLocalized);
    }

    protected function setLongDomain(): void
    {
        $this->domain = $this->longDomain; // we set this for the request

        app('config')->set('localization.domain', $this->longDomain); //simulates env APP_DOMAIN
    }
}
