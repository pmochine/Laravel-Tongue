<?php

namespace Pmochine\LaravelTongue\Tests;

use Pmochine\LaravelTongue\Exceptions\SupportedLocalesNotDefined;
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
    * BUT now is redirecting since beautify is false as well
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
    public function it_returns_the_current_app_locale() 
    {
        $this->app->setLocale('fr');

        $this->assertEquals(app('tongue')->current(), $this->app->getLocale());
    }

    /** @test */
    public function it_returns_the_current_name_or_other_keys() 
    {
        $this->app->setLocale('de');

        $this->assertEquals(app('tongue')->current('name'), 'German');

        $this->assertEquals(app('tongue')->current('script'), 'Latn');

        $this->assertEquals(app('tongue')->current('native'), 'Deutsch');

        $this->assertEquals(app('tongue')->current('regional'), 'de_DE');
    }

    /** @test */
    public function it_throws_an_exception_if_key_does_not_exist() 
    {
        $this->app->setLocale('de');

        $this->expectException(SupportedLocalesNotDefined::class);

        app('tongue')->current('namr');
    }


    /** @test */
    public function it_returns_the_direction_of_the_spoken_language() 
    {
        $this->assertEquals('ltr', app('tongue')->leftOrRight());

        $this->app->setLocale('es');

        $this->assertEquals('ltr',app('tongue')->leftOrRight());
    }

    /**
     * Important to note. I just don't want to test the full array...
     * @test
     */
    public function it_returns_the_available_locales()
    {
        $supportedLocales = ['en', 'es', 'fr', 'de'];

        $this->app['config']->set('localization.supportedLocales', $supportedLocales);

        $this->assertEquals($supportedLocales, app('tongue')->speaking()->all());
    }

  


}
