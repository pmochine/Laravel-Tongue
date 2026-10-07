<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Pmochine\LaravelTongue\Misc\Config;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

class ConfigTest extends TestCase
{
    #[Test]
    public function it_can_read_domain_from_config()
    {
        app('config')->set('localization.domain', $domain = '155ad73e.eu.ngrok.io');

        $this->assertEquals($domain, Config::domain());
    }

    #[Test]
    public function it_can_read_subdomains_from_config()
    {
        app('config')->set('localization.subdomains', $subdomains = ['admin']);

        $this->assertEquals($subdomains, Config::subdomains());
    }

    #[Test]
    public function it_can_read_aliases_from_config()
    {
        app('config')->set('localization.aliases', $aliases = ['gewinnen' => 'de']);

        $this->assertEquals($aliases, Config::aliases());
    }

    #[Test]
    public function it_can_read_alias_urls_from_config()
    {
        $this->assertFalse(Config::aliasUrls());

        app('config')->set('localization.alias_urls', true);

        $this->assertTrue(Config::aliasUrls());
    }

    #[Test]
    public function it_can_read_beautify_from_config()
    {
        $this->assertTrue(Config::beautify());
    }

    #[Test]
    public function it_can_read_fallbackLocale_from_config()
    {
        $this->assertEquals($this->defaultLocale, Config::fallbackLocale());
    }

    #[Test]
    public function it_can_read_supportedLocales_from_config()
    {
        $this->assertIsArray(Config::supportedLocales());
        $this->assertCount(5, Config::supportedLocales());
    }

    #[Test]
    public function it_can_read_acceptLanguage_from_config()
    {
        $this->assertTrue(Config::acceptLanguage());
    }

    #[Test]
    public function it_can_read_cookieLocalization_from_config()
    {
        $this->assertTrue(Config::cookieLocalization());
    }

    #[Test]
    public function it_can_read_preventRedirect_from_config()
    {
        $this->assertFalse(Config::preventRedirect());
    }
}
