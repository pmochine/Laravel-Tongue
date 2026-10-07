<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

class TongueTest extends TestCase
{
    protected $pathLocalized = 'localized';
    protected $pathNotLocalized = 'not-localized';

    #[Test]
    public function it_does_not_redirect_when_middleware_is_not_used()
    {
        $response = $this->sendRequest('GET', $this->pathNotLocalized);

        $response->assertOk();

        app('config')->set('localization.beautify_url', false);

        $response = $this->sendRequest('GET', $this->pathNotLocalized);

        $response->assertOk();
    }

    #[Test]
    public function it_does_not_redirect_if_locale_is_not_missing()
    {
        //default locale is en
        $this->assertEquals(app()->getLocale(), 'en');

        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals(app()->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_detects_and_sets_the_locale_from_the_url()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_detects_and_sets_the_locale_from_the_cookies()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('tongue')->twister());

        $response->assertStatus(302);

        $response->assertRedirect($this->getUri($this->pathLocalized, 'de'));
    }

    /**
     * It ignores cookies when cookie localization is disabled.
     * Important! Since beautify is set, it does not redirect!
     */
    #[Test]
    public function it_ignoes_cookies_when_cookie_localization_is_disabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    /**
     * It ignores cookies when cookie localization is disabled.
     * BUT now is redirecting since beautify is false as well.
     */
    #[Test]
    public function it_ignoes_cookies_and_redirects_when_beautify_is_deactivated()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        app('config')->set('localization.beautify_url', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('tongue')->twister());

        $response->assertStatus(302);

        $response->assertRedirect($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    #[Test]
    public function it_detects_and_set_the_locale_from_the_browser()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('tongue')->twister());

        $response->assertStatus(302);

        $response->assertRedirect($this->getUri($this->pathLocalized, 'de'));
    }

    #[Test]
    public function it_ignores_browser_settings_when_acceptLanguage_is_disabled()
    {
        // Disable browser localization
        app('config')->set('localization.acceptLanguage', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_detects_and_set_the_locale_from_the_config()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_detects_and_set_the_locale_from_the_config_and_redirects()
    {
        app('config')->set('localization.beautify_url', false);

        $response = $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('tongue')->twister());

        $response->assertStatus(302);

        $response->assertRedirect($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    #[Test]
    public function it_responds_with_the_cookie_locale()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertTrue($this->responseHasCookies($response, ['tongue-locale' => 'de']));

        $response->assertOk();
    }

    #[Test]
    public function it_does_not_respond_with_the_cookie_locale_when_cookie_disabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertFalse($this->responseHasCookies($response, ['tongue-locale' => 'de']));

        $response->assertOk();
    }

    #[Test]
    public function it_redirects_to_default_when_locale_is_not_found_on_supported_list()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'ff');

        $response->assertRedirect($this->getUri($this->pathLocalized));

        app('config')->set('localization.beautify_url', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'ff');

        $response->assertRedirect($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    #[Test]
    public function it_does_not_redirect_when_subdomain_is_white_listed()
    {
        app('config')->set('localization.subdomains', ['admin']);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'admin');

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_redirects_to_default_when_subdomain_is_not_found_on_subdomains_list()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'admin');

        $response->assertRedirect($this->getUri($this->pathLocalized));
    }

    #[Test]
    public function it_sets_the_language_of_the_page_according_to_the_aliases()
    {
        app('config')->set('localization.aliases', ['gewinnen' => 'de', 'winning' => 'en']);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();

        $response = $this->sendRequest('GET', $this->pathLocalized, 'winning');

        $this->assertEquals($this->app->getLocale(), 'en');

        $this->assertFalse(app('tongue')->twister());

        $response->assertOk();
    }

    #[Test]
    public function it_redirects_to_default_when_aliases_does_not_exist()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $response->assertRedirect($this->getUri($this->pathLocalized));
    }

    #[Test]
    public function it_redirects_to_default_when_aliases_locale_does_not_exist_in_supported_list()
    {
        app('config')->set('localization.aliases', ['gewinnen' => 'ff']);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $response->assertRedirect($this->getUri($this->pathLocalized));
    }

    #[Test]
    public function it_can_find_locale_from_complicated_domains()
    {
        //to set the request host to this domain
        $this->domain = '155ad73e.eu.ngrok.io';
        //to get the domain from env
        app('config')->set('localization.domain', '155ad73e.eu.ngrok.io');

        $response = $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals(app()->getLocale(), 'en');

        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals(app()->getLocale(), 'de');

        $response->assertOk();
    }
}
