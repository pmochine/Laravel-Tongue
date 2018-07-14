<?php

namespace Pmochine\LaravelTongue\Tests;

use Pmochine\LaravelTongue\Facades\Tongue;
use Pmochine\LaravelTongue\ServiceProvider;

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


    /**
     * It returns the available locales.
     *
     * @return void
     */
    // public function testItReturnsTheAvailableLocales()
    // {
       
    //     $supportedLocales = ['en', 'es', 'fr', 'de'];

    //     $this->app['config']->set('localization.supportedLocales', $supportedLocales);

    //     $this->assertEquals($supportedLocales, app('localization.localize')->getAvailableLocales());
    // }

    /**
     * It should not redirect a non-localized route.
     *
     * @return void
     */
    public function testItDoesNotRedirectANonLocalizedRoute()
    {
        $this->sendRequest('GET', $this->pathNotLocalized);

        $this->assertResponseOk();
    }

    /**
     * It should not redirect if the locale is not missing.
     *
     * @return void
     */
    public function testItDoesNotRedirectIfLocaleIsNotMissing()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals(app()->getLocale(), 'de');

        $this->assertFalse(app('localization.localize')->shouldRedirect());

        $this->assertResponseOk();
    }

    /**
     * It detects and sets the locale from the url.
     *
     * @return void
     */
    public function testItDetectsAndSetsTheLocaleFromTheUrl()
    {
        $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('localization.localize')->shouldRedirect());

        $this->assertResponseOk();
    }

    /**
     * It detects and sets the locale from an aliased url subdomain.
     *
     * @return void
     */
    public function testItDetectsAndSetsTheLocaleFromAliasedSubdomain()
    {
        $aliases = [
            'german' => 'de',
        ];

        $this->app['config']->set('localization.aliases', $aliases);

        $this->sendRequest('GET', $this->pathLocalized, 'german');

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertFalse(app('localization.localize')->shouldRedirect());

        $this->assertResponseOk();
    }

    /**
     * It detects and sets the locale from the cookies.
     *
     * @return void
     */
    public function testItDetectsAndSetsTheLocaleFromTheCookies()
    {
        $this->sendRequest('GET', $this->pathLocalized, null, [], ['locale' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('localization.localize')->shouldRedirect());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, 'de'));
    }

    /**
     * It ignores cookies when cookie localization is disabled.
     *
     * @return void
     */
    public function testItIgnoresCookiesWhenCookieLocalizationIsDisabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $this->sendRequest('GET', $this->pathLocalized, null, [], ['locale' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('localization.localize')->shouldRedirect());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /**
     * It detects and sets the locale from the browser language settings.
     *
     * @return void
     */
    public function testItDetectsAndSetsTheLocaleFromTheBrowser()
    {
        $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->app->getLocale(), 'de');

        $this->assertTrue(app('localization.localize')->shouldRedirect());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, 'de'));
    }

    /**
     * It ignores browser settings when browser localization is disabled.
     *
     * @return void
     */
    public function testItIgnoresBrowserSettingsWhenBrowserLocalizationIsDisabled()
    {
        // Disable browser localization
        app('config')->set('localization.browser_localization', false);

        $this->sendRequest('GET', $this->pathLocalized, null, [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('localization.localize')->shouldRedirect());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /**
     * It detects and sets the locale from the default locale setting.
     *
     * @return void
     */
    public function testItDetectsAndSetsTheLocaleFromTheConfig()
    {
        $this->sendRequest('GET', $this->pathLocalized);

        $this->assertEquals($this->defaultLocale, $this->app->getLocale());

        $this->assertTrue(app('localization.localize')->shouldRedirect());

        $this->assertResponseStatus(302);

        $this->assertRedirectedTo($this->getUri($this->pathLocalized, $this->defaultLocale));
    }

    /**
     * It responds with the cookie locale.
     *
     * @return void
     */
    public function testItRespondsWithTheCookieLocale()
    {
        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertTrue($this->responseHasCookies($response, ['locale' => 'de']));
        $this->assertResponseOk();
    }

    /**
     * it does not respond with the cookie locale when cookie localization is disabled.
     *
     * @return void
     */
    public function testItDoesNotRespondWithTheCookieLocaleWhenCookieLocalizationIsDisabled()
    {
        // Disable cookie localization
        app('config')->set('localization.cookie_localization', false);

        $response = $this->sendRequest('GET', $this->pathLocalized, 'de');

        $this->assertFalse($this->responseHasCookies($response, ['locale' => 'de']));
        $this->assertResponseOk();
    }
}
