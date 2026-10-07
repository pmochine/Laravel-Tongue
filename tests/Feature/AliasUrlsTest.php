<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

/**
 * The option "alias_urls" uses the aliases in the URLs that Tongue builds (#47, #52).
 */
class AliasUrlsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app('config')->set('localization.aliases', ['gewinnen' => 'de', 'www' => 'es']);
    }

    #[Test]
    public function it_keeps_the_locale_as_subdomain_when_the_option_is_off()
    {
        $this->setRequestContext('GET', 'localized');

        $this->assertEquals($this->getUri('guten-morgen', 'de'), app('dialect')->translate('Tongue::routes.good_morning', null, 'de'));

        $this->sendRequest('GET', 'localized', 'de')->assertOk();
    }

    #[Test]
    public function it_uses_the_alias_in_translated_urls()
    {
        app('config')->set('localization.alias_urls', true);

        $this->setRequestContext('GET', 'localized');

        $this->assertEquals($this->getUri('guten-morgen', 'gewinnen'), app('dialect')->translate('Tongue::routes.good_morning', null, 'de'));
        $this->assertEquals($this->getUri('localized', 'www'), app('dialect')->current('es'));
        $this->assertEquals($this->getUri('localized', 'gewinnen'), app('dialect')->translateAll()['de']);
        $this->assertEquals($this->getUri('localized', 'fr'), app('dialect')->translateAll()['fr']);
    }

    #[Test]
    public function it_uses_the_alias_in_redirect_urls()
    {
        app('config')->set('localization.alias_urls', true);

        $this->setRequestContext('GET', 'localized', null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->getUri('localized', 'gewinnen'), app('dialect')->redirectUrl());
        $this->assertEquals($this->getUri('home', 'www'), app('dialect')->redirectUrl($this->getUri('home', 'de'), 'es'));
    }

    #[Test]
    public function it_redirects_the_browser_language_to_the_alias()
    {
        app('config')->set('localization.alias_urls', true);

        $this->sendRequest('GET', 'localized', null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de'])
            ->assertRedirect($this->getUri('localized', 'gewinnen'));
    }

    #[Test]
    public function it_redirects_the_locale_subdomain_to_the_alias()
    {
        app('config')->set('localization.alias_urls', true);

        $this->sendRequest('GET', 'localized', 'de')
            ->assertRedirect($this->getUri('localized', 'gewinnen'));

        $response = $this->sendRequest('GET', 'localized', 'gewinnen');

        $response->assertOk();
        $this->assertEquals('de', app()->getLocale());
        $this->assertFalse(app('tongue')->twister());
    }

    #[Test]
    public function the_beautiful_url_of_the_fallback_locale_wins_over_its_alias()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.aliases', ['english' => 'en']);

        $this->sendRequest('GET', 'localized')->assertOk();

        $this->assertEquals($this->getUri('localized'), app('dialect')->current('en'));
    }

    #[Test]
    public function an_alias_that_is_a_locale_code_causes_no_redirect_loop()
    {
        app('config')->set('localization.beautify_url', false);
        app('config')->set('localization.aliases', ['en' => 'de']);

        $this->sendRequest('GET', 'localized', 'en')->assertOk();

        app('config')->set('localization.alias_urls', true);

        $this->sendRequest('GET', 'localized', 'en')->assertOk();
        $this->sendRequest('GET', 'localized', 'de')->assertOk();

        $this->assertEquals($this->getUri('localized', 'de'), app('dialect')->current('de'));
    }

    #[Test]
    public function it_ignores_an_alias_that_is_a_whitelisted_subdomain()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.aliases', ['www' => 'de']);
        app('config')->set('localization.subdomains', ['www']);

        $this->setRequestContext('GET', 'localized');

        $this->assertEquals($this->getUri('localized', 'de'), app('dialect')->current('de'));
    }

    #[Test]
    public function the_alias_of_the_fallback_locale_redirects_to_the_beautiful_url()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.aliases', ['english' => 'en']);

        // Like without aliases: the link keeps the subdomain, so the cookie switches before the redirect.
        $this->sendRequest('GET', 'localized', 'de');

        $this->assertEquals($this->getUri('localized', 'en'), app('dialect')->current('en'));
        $this->assertEquals($this->getUri('localized', 'en'), app('dialect')->redirectUrl(null, 'en'));

        $this->sendRequest('GET', 'localized', 'english')->assertRedirect($this->getUri('localized'));
    }

    #[Test]
    public function a_second_alias_redirects_to_the_first_alias()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.aliases', ['gewinnen' => 'de', 'deutsch' => 'de']);

        $this->sendRequest('GET', 'localized', 'deutsch')->assertRedirect($this->getUri('localized', 'gewinnen'));
    }

    #[Test]
    public function aliases_are_case_insensitive_like_hosts()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.beautify_url', false);

        // "DE" is the locale "de", so Tongue ignores the alias
        app('config')->set('localization.aliases', ['DE' => 'de']);

        $this->sendRequest('GET', 'localized', 'de')->assertOk();

        app('config')->set('localization.aliases', ['Gewinnen' => 'de']);

        $this->setRequestContext('GET', 'localized');
        $this->assertEquals($this->getUri('localized', 'gewinnen'), app('dialect')->current('de'));

        $this->sendRequest('GET', 'localized', 'gewinnen')->assertOk();
        $this->assertEquals('de', app()->getLocale());
    }

    #[Test]
    public function an_alias_can_be_the_first_label_of_the_domain()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.beautify_url', false);
        app('config')->set('localization.aliases', ['laraveltongue' => 'de']);

        $this->setRequestContext('GET', 'localized', 'en');
        $this->assertEquals($this->getUri('localized', 'laraveltongue'), app('dialect')->current('de'));

        $this->sendRequest('GET', 'localized', 'laraveltongue')->assertOk();
        $this->assertEquals('de', app()->getLocale());
    }

    #[Test]
    public function an_alias_can_be_a_number()
    {
        app('config')->set('localization.beautify_url', false);
        app('config')->set('localization.aliases', ['123' => 'de']);

        $this->sendRequest('GET', 'localized', '123')->assertOk();
        $this->assertEquals('de', app()->getLocale());

        app('config')->set('localization.alias_urls', true);

        $this->assertEquals($this->getUri('localized', '123'), app('dialect')->current('de'));
    }

    #[Test]
    public function the_urls_use_only_an_alias_that_the_detection_maps_back_to_the_locale()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.beautify_url', false);
        app('config')->set('localization.aliases', ['Deutsch' => 'de', 'deutsch' => 'fr']);

        $this->setRequestContext('GET', 'localized', 'en');

        $this->assertEquals($this->getUri('localized', 'deutsch'), app('dialect')->current('de'));
        $this->assertEquals($this->getUri('localized', 'fr'), app('dialect')->current('fr'));
    }
}
