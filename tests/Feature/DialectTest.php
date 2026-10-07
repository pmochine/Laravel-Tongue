<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

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



    #[Test]
    public function it_reaches_translated_routes()
    {
        $response = $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $response->assertOk();

        app('config')->set('localization.beautify_url', false);

        $response = $this->sendRequest('GET', $this->enPathWithoutParameter, 'en');

        $response->assertOk();

        //IMPORTANT NOT DONE
    }

    #[Test]
    public function it_returns_a_redirect_url()
    {
        $this->setRequestContext('GET', $this->dePathWithoutParameter, null, [], ['tongue-locale' => 'de']);

        $this->assertEquals($this->getUri($this->dePathWithoutParameter, 'de'), app('dialect')->redirectUrl());

        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['tongue-locale' => 'en']);

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, ''), app('dialect')->redirectUrl());

        app('config')->set('localization.beautify_url', false);

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->redirectUrl());
    }

    #[Test]
    public function it_redirects_url_into_a_specific_language()
    {
        $this->setRequestContext('GET', $this->dePathWithoutParameter, null, [], ['tongue-locale' => 'de']);

        $enUri = $this->getUri('home', 'en');

        $this->assertEquals($enUri, app('dialect')->redirectUrl($enUri, 'en'));

        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['tongue-locale' => 'en']);

        $deUri = $this->getUri('home', 'de');

        $this->assertEquals($deUri, app('dialect')->redirectUrl($deUri, 'de'));
    }

    #[Test]
    public function it_redirects_url_to_correct_language()
    {
        // This is an example when we are changing the language. The "standard" locale is set to "en"
        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['tongue-locale' => 'en']);

        $enUri = $this->getUri('home');
        $deUri = $this->getUri('home', 'de');

        $this->assertEquals($enUri, app('dialect')->redirectUrl($deUri));

        // Now German is set as target language
        $this->setRequestContext('GET', $this->enPathWithoutParameter, null, [], ['tongue-locale' => 'de']);

        $enUri = $this->getUri('home');
        $deUri = $this->getUri('home', 'de');

        $this->assertEquals($deUri, app('dialect')->redirectUrl($enUri));

        $enUri = $this->getUri('home', 'en');
        $deUri = $this->getUri('home', 'de');

        $this->assertEquals($deUri, app('dialect')->redirectUrl($enUri));
    }

    #[Test]
    public function it_translates_the_current_route()
    {
        $response = $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->current('en'));

        $this->refresh();

        $response = $this->sendRequest('GET', $this->enPathWithParameter1, 'en');

        $this->assertEquals($this->getUri($this->dePathWithParameter1, 'de'), app('dialect')->current('de'));
    }

    #[Test]
    public function it_returns_translated_versions_of_the_current_route_for_available_locales()
    {
        $response = $this->sendRequest('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals($this->getUri($this->enPathWithoutParameter, 'en'), app('dialect')->translateAll()['en']);

        $this->refresh();

        $response = $this->sendRequest('GET', $this->enPathWithParameter1, 'en');

        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1), //no subdomain because of beautify
            'de' => $this->getUri($this->dePathWithParameter1, 'de'),
        ], Arr::only(app('dialect')->translateAll(false), ['en', 'de']));

        //With beautify_off
        app('config')->set('localization.beautify_url', false);

        $this->assertEquals([
            'en' => $this->getUri($this->enPathWithParameter1, 'en'),
            'de' => $this->getUri($this->dePathWithParameter1, 'de'),
        ], Arr::only(app('dialect')->translateAll(false), ['en', 'de']));
    }

    #[Test]
    public function it_uses_the_url_of_the_current_request_for_each_translation()
    {
        $this->setRequestContext('GET', 'localized', 'de');

        $this->assertEquals($this->getUri('localized', 'fr'), app('dialect')->translate('Tongue::routes.unknown', null, 'fr'));

        $this->setRequestContext('GET', 'not-localized', 'de');

        $this->assertEquals($this->getUri('not-localized', 'fr'), app('dialect')->translate('Tongue::routes.unknown', null, 'fr'));
    }

    #[Test]
    public function it_translates_the_current_route_of_a_view_route()
    {
        $response = $this->sendRequest('GET', 'privacy', 'de');

        $response->assertOk();

        $this->assertEquals($this->getUri('privacy', 'fr'), trim($response->getContent()));
    }

    #[Test]
    public function it_translates_the_current_route_with_a_bound_parameter()
    {
        app('router')->bind('username', function ($value) {
            return new \ArrayObject(['username' => $value]);
        });

        $this->sendRequest('GET', $this->enPathWithParameter1, 'en');

        $this->assertEquals($this->getUri($this->dePathWithParameter1, 'de'), app('dialect')->current('de'));
    }

    #[Test]
    public function it_interprets_a_translated_route_path()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->dePathWithoutParameter, app('dialect')->interpret($this->routeNameWithoutParameter));

        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals($this->enPathWithParameter, app('dialect')->interpret($this->routeNameWithParameter));
    }

    #[Test]
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
