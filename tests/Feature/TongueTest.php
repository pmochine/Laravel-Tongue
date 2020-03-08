<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use Pmochine\LaravelTongue\Facades\Tongue;
use Pmochine\LaravelTongue\ServiceProvider;
use Pmochine\LaravelTongue\Tests\TestCase;

class TongueTest extends TestCase
{
    protected $pathLocalized = 'localized';
    protected $pathNotLocalized = 'not-localized';

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /**
     * Get package aliases.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'tongue' => Tongue::class,
        ];
    }

    /** @test */
    public function it_does_not_redirect_when_middleware_is_not_used()
    {
        $this->sendRequest('GET', $this->pathNotLocalized);

        $this->assertResponseOk();

        app('config')->set('localization.beautify_url', false);

        $this->sendRequest('GET', $this->pathNotLocalized);

        $this->assertResponseOk();
    }

    /** @test */
    public function it_does_not_redirect_if_locale_is_not_missing()
    {
        //default locale is en
        $this->assertEquals(app()->getLocale(), 'en');

        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals(app()->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_detects_and_sets_the_locale_from_the_url()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_detects_and_sets_the_locale_from_the_cookies()
    {
        $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('tongue')->twister());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, 'de'));
    }

    /**
     * It ignores cookies when cookie localization is disabled.
     * Important! Since beautify is set, it does not redirect!
     *
     * @return void
     * @test
     */
    public function it_ignoes_cookies_when_cookie_localization_is_disabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /**
     * It ignores cookies when cookie localization is disabled.
     * BUT now is redirecting since beautify is false as well.
     *
     * @return void
     * @test
     */
    public function it_ignoes_cookies_and_redirects_when_beautify_is_deactivated()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        app('config')->set('localization.beautify_url', false);

        $this->sendRequest('GET', $this->pathLocalized, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('tongue')->twister());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_detects_and_set_the_locale_from_the_browser()
    {
        $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('tongue')->twister());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, 'de'));
    }

    /** @test */
    public function it_ignores_browser_settings_when_acceptLanguage_is_disabled()
    {
        // Disable browser localization
        app('config')->set('localization.acceptLanguage', false);

        $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_detects_and_set_the_locale_from_the_config()
    {
        $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_detects_and_set_the_locale_from_the_config_and_redirects()
    {
        app('config')->set('localization.beautify_url', false);

        $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('tongue')->twister());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_responds_with_the_cookie_locale()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertTrue($this->responseHasCookies($response, ['tongue-locale' => 'de']));

        $this->assertResponseOk();
    }

    /** @test */
    public function it_does_not_respond_with_the_cookie_locale_when_cookie_disabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertFalse($this->responseHasCookies($response, ['tongue-locale' => 'de']));

        $this->assertResponseOk();
    }

    /** @test */
    public function it_redirects_to_default_when_locale_is_not_found_on_supported_list()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'ff');

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_does_not_redirect_when_subdomain_is_white_listed()
    {
        app('config')->set('localization.subdomains', ['admin']);

        $this->sendRequest('GET', $this->pathLocalized, 'admin');

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_redirects_to_default_when_subdomain_is_not_found_on_subdomains_list()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'admin');

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_sets_the_language_of_the_page_according_to_the_aliases()
    {
        app('config')->set('localization.aliases', ['gewinnen' => 'de', 'winning' => 'en']);

        $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();

        $this->sendRequest('GET', $this->pathLocalized, 'winning');

        $this->assertEquals($this->app->getLocale(), 'en');

        $this->assertFalse(app('tongue')->twister());

        $this->assertResponseOk();
    }

    /** @test */
    public function it_redirects_to_default_when_aliases_does_not_exist()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_redirects_to_default_when_aliases_locale_does_not_exist_in_supported_list()
    {
        app('config')->set('localization.aliases', ['gewinnen' => 'ff']);

        $this->sendRequest('GET', $this->pathLocalized, 'gewinnen');

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /** @test */
    public function it_can_find_locale_from_complicated_domains()
    {
        //to set the request host to this domain
        $this->domain = '155ad73e.eu.ngrok.io';
        //to get the domain from env
        app('config')->set('localization.domain', '155ad73e.eu.ngrok.io');

        $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals(app()->getLocale(), 'en');

        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals(app()->getLocale(), 'de');

        $this->assertResponseOk();
    }
}
