<?php

namespace Pmochine\LaravelTongue\Tests;

class RouterTest extends TestCase
{
    protected $routeNameWithoutParameter = 'Localize::routes.good_morning';
    protected $dePathWithoutParameter = 'guten-morgen';
    protected $enPathWithoutParameter = 'good-morning';

    protected $routeNameWithParameter = 'Localize::routes.hello_user';
    protected $dePathWithParameter = 'hallo/{username}';
    protected $enPathWithParameter = 'hello/{username}';
    protected $dePathWithParameter1 = 'hallo/samplename';
    protected $enPathWithParameter1 = 'hello/samplename';
    protected $routeParameters = ['username' => 'samplename'];

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Hoyvoy\Localization\LocalizationServiceProvider',
        ];
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
            'Localize' => 'Hoyvoy\Localization\Facades\Localize',
            'Router'   => 'Hoyvoy\Localization\Facades\Router',
        ];
    }

    /**
     * Sets domain alias german to de.
     *
     * @return void
     */
    protected function setDomainAliases()
    {
        $aliases = [
            'german' => 'de',
        ];

        $this->app['config']->set('localization.aliases', $aliases);
    }

    /**
     * It translates routes.
     *
     * @return void
     */
    public function testItReachesTranslatedRoutes()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');
        $this->assertResponseOk();

        $this->sendRequest('GET', $this->enPathWithoutParameter, 'en');
        $this->assertResponseOk();

        $this->setDomainAliases();
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'german');
        $this->assertResponseOk();
    }

    /**
     * It returns a redirect URL.
     *
     * @return void
     */
    public function testItReturnsARedirectUrl()
    {
        $this->setRequestContext('GET', $this->dePathWithoutParameter, null, [], ['locale' => 'de']);
        $this->assertEquals($this->getUri($this->dePathWithoutParameter, 'de'),
            app('localization.router')->getRedirectURL());

        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['locale' => 'en']);
        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'),
            app('localization.router')->getRedirectURL());

        $this->setDomainAliases();
        $this->setRequestContext('GET', $this->dePathWithoutParameter, null, [], ['locale' => 'de']);
        $this->assertEquals($this->getUri($this->dePathWithoutParameter, 'german'),
            app('localization.router')->getRedirectURL());
    }

    /**
     * It translates the current route.
     *
     * @return void
     */
    public function testItTranslatesTheCurrentRoute()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'),
            app('localization.router')->current('en'));

        $this->refresh();

        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');
        $this->assertEquals($this->getUri($this->dePathWithParameter1, 'de'),
            app('localization.router')->current('de'));

        $this->refresh();

        $this->setDomainAliases();
        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');
        $this->assertEquals($this->getUri($this->dePathWithParameter1, 'german'),
            app('localization.router')->current('de'));
    }

    /**
     * It returns translated versions of the current route for all available locales.
     *
     * @return void
     */
    public function testItReturnsTranslatedVersionsOfTheCurrentRouteForAvailableLocales()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');
        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithoutParameter, 'en'),
        ], app('localization.router')->getCurrentVersions());

        $this->refresh();

        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');
        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1, 'en'),
            'de' => $this->getUri($this->dePathWithParameter1, 'de'),
        ], app('localization.router')->getCurrentVersions(false));

        $this->refresh();

        $this->setDomainAliases();
        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');
        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1, 'en'),
            'de' => $this->getUri($this->dePathWithParameter1, 'german'),
        ], app('localization.router')->getCurrentVersions(false));
    }

    /**
     * It resolves a translated route path.
     *
     * @return void
     */
    public function testItResolvesATranslatedRoutePath()
    {
        $this->setRequestContext('GET', '', 'de');
        $this->assertEquals($this->dePathWithoutParameter,
            app('localization.router')->resolve($this->routeNameWithoutParameter));

        $this->setRequestContext('GET', '', 'en');
        $this->assertEquals($this->enPathWithParameter,
            app('localization.router')->resolve($this->routeNameWithParameter));
    }

    /**
     * It translates a route into an url.
     *
     * @return void
     */
    public function testItTranslatesARouteIntoAnUrl()
    {
        $this->setRequestContext('GET', '');

        $this->assertEquals(
            $this->getUri($this->dePathWithoutParameter, 'de'),
            app('localization.router')->url($this->routeNameWithoutParameter, null, 'de')
        );

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1, 'en'),
            app('localization.router')->url($this->routeNameWithParameter, $this->routeParameters, 'en')
        );

        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri($this->dePathWithParameter1, 'de'),
            app('localization.router')->url($this->routeNameWithParameter, $this->routeParameters)
        );

        $this->setDomainAliases();
        $this->assertEquals(
            $this->getUri($this->dePathWithParameter1, 'german'),
            app('localization.router')->url($this->routeNameWithParameter, $this->routeParameters)
        );

        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1, 'en'),
            app('localization.router')->url($this->routeNameWithParameter, $this->routeParameters)
        );
    }
}
