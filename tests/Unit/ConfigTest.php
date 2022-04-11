<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\ServiceProvider;
use Pmochine\LaravelTongue\Tests\TestCase;

class ConfigTest extends TestCase
{
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

    /** @test */
    public function it_can_read_domain_from_config()
    {
        app('config')->set('localization.domain', $domain = '155ad73e.eu.ngrok.io');

        $this->assertEquals($domain, Config::domain());
    }

    /** @test */
    public function it_can_read_subdomains_from_config()
    {
        app('config')->set('localization.subdomains', $subdomains = ['admin']);

        $this->assertEquals($subdomains, Config::subdomains());
    }

    /** @test */
    public function it_can_read_aliases_from_config()
    {
        app('config')->set('localization.aliases', $aliases = ['gewinnen' => 'de']);

        $this->assertEquals($aliases, Config::aliases());
    }

    /** @test */
    public function it_can_read_beautify_from_config()
    {
        $this->assertTrue(Config::beautify());
    }

    /** @test */
    public function it_can_read_fallbackLocale_from_config()
    {
        $this->assertEquals($this->defaultLocale, Config::fallbackLocale());
    }

    /** @test */
    public function it_can_read_supportedLocales_from_config()
    {
        $this->assertIsArray(Config::supportedLocales());
        $this->assertCount(5, Config::supportedLocales());
    }

    /** @test */
    public function it_can_read_acceptLanguage_from_config()
    {
        $this->assertTrue(Config::acceptLanguage());
    }

    /** @test */
    public function it_can_read_cookieLocalization_from_config()
    {
        $this->assertTrue(Config::cookieLocalization());
    }

    /** @test */
    public function it_can_read_preventRedirect_from_config()
    {
        $this->assertFalse(Config::preventRedirect());
    }
}
