<?php

namespace Pmochine\LaravelTongue\Tests;

use Pmochine\LaravelTongue\Facades\Dialect;
use Pmochine\LaravelTongue\ServiceProvider;

class DialectTest extends TestCase
{
    protected $routeNameWithoutParameter = 'Tongue::routes.good_morning';
    protected $dePathWithoutParameter = 'guten-morgen';
    protected $enPathWithoutParameter = 'good-morning';

    protected $routeNameWithParameter = 'Tongue::routes.hello_user';
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
            'dialect' => Dialect::class,
        ];
    }

    /** @test */
    public function it_reaches_translated_routes()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertResponseOk();

        app('config')->set('localization.beautify_url', false);

        $this->sendRequest('GET', $this->enPathWithoutParameter, 'en');

        $this->assertResponseOk();

        //IMPORTANT NOT DONE
    }

    /** @test */
    public function it_returns_a_redirect_url()
    {
        $this->setRequestContext('GET', $this->dePathWithoutParameter, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->getUri($this->dePathWithoutParameter, 'de'), app('dialect')->redirectUrl());

        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['tongue-locale' => 'en']);

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->redirectUrl());
    }

    /** @test */
    public function it_translates_the_current_route()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->current('en'));

        $this->refresh();

        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');

        $this->assertEquals($this->getUri($this->dePathWithParameter1, 'de'), app('dialect')->current('de'));
    }

    /** @test */
    public function it_returns_translated_versions_of_the_current_route_for_available_locales()
    {
        $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->translateAll()['en']);

        $this->refresh();

        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');

        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1), //no subdomain because of beautify
            'de' => $this->getUri($this->dePathWithParameter1, 'de'),
        ], array_only(app('dialect')->translateAll(false), ['en', 'de']));

        //With beautify_off
        app('config')->set('localization.beautify_url', false);

        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1, 'en'),
            'de' => $this->getUri($this->dePathWithParameter1, 'de'),
        ], array_only(app('dialect')->translateAll(false), ['en', 'de']));
    }

    /** @test */
    public function it_interprets_a_translated_route_path()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->dePathWithoutParameter, app('dialect')->interpret($this->routeNameWithoutParameter));

        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals($this->enPathWithParameter, app('dialect')->interpret($this->routeNameWithParameter));
    }

    /** @test */
    public function it_translates_a_route_into_an_url()
    {
        //when beautify is off
        app('config')->set('localization.beautify_url', false);

        $this->setRequestContext('GET', '');

        $this->assertEquals(
            $this->getUri($this->dePathWithoutParameter, 'de'),
            app('dialect')->translate($this->routeNameWithoutParameter, null, 'de')
        );

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1, 'en'),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters, 'en')
        );

        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri($this->dePathWithParameter1, 'de'),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters)
        );

        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1, 'en'),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters)
        );

        //beautify is on, so some url won't have subdomains
        app('config')->set('localization.beautify_url', true);

        $this->setRequestContext('GET', '');

        $this->assertEquals(
            $this->getUri($this->dePathWithoutParameter, 'de'),
            app('dialect')->translate($this->routeNameWithoutParameter, null, 'de')
        );

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters, 'en')
        );

        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri($this->dePathWithParameter1, 'de'),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters)
        );

        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1),
            app('dialect')->translate($this->routeNameWithParameter, $this->routeParameters)
        );
    }
}
