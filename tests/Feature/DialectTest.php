<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\Fixtures\Post;
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
    public function it_translates_the_current_route_when_the_translated_route_has_a_name()
    {
        $this->sendRequest('GET', 'gute-nacht', 'de');

        $this->assertEquals($this->getUri('good-night', 'en'), app('dialect')->current('en'));
    }

    #[Test]
    public function it_translates_a_route_name_into_an_url()
    {
        $this->setRequestContext('GET', $this->dePathWithoutParameter, 'de');

        // Named route with a translated path. The link to the fallback locale keeps the subdomain,
        // so the cookie switches to "en" before the middleware redirects to the beautiful URL.
        $this->assertEquals($this->getUri('good-night', 'en'), app('dialect')->translate('good_night', [], 'en'));
        $this->assertEquals($this->getUri('gute-nacht', 'de'), app('dialect')->translate('good_night'));

        // Named route without a translated path (#53)
        $this->assertEquals($this->getUri('privacy', 'fr'), app('dialect')->translate('privacy', [], 'fr'));
    }

    #[Test]
    public function it_builds_the_url_of_the_home_route_without_a_trailing_slash()
    {
        $this->sendRequest('GET', '', 'de');

        $this->assertEquals('https://fr.laraveltongue.dev', app('dialect')->current('fr'));

        $this->setRequestContext('GET', $this->dePathWithoutParameter, 'de');

        $this->assertEquals('https://fr.laraveltongue.dev', app('dialect')->translate('home', [], 'fr'));
    }

    #[Test]
    public function it_removes_missing_optional_parameters()
    {
        $this->sendRequest('GET', 'blog', 'de');

        $this->assertEquals($this->getUri('blog', 'fr'), app('dialect')->current('fr'));
        $this->assertEquals($this->getUri('blog', 'fr'), app('dialect')->translate('blog', [], 'fr'));

        $this->sendRequest('GET', 'blog/2', 'de');

        $this->assertEquals($this->getUri('blog/2', 'fr'), app('dialect')->current('fr'));
    }

    #[Test]
    public function it_keeps_encoded_characters_of_route_parameters()
    {
        foreach (['a%23b', 'a%3Fb', '100%25', 'John%20Doe'] as $username) {
            $this->sendRequest('GET', 'hello/'.$username, 'en');

            $this->assertEquals($this->getUri('hallo/'.$username, 'de'), app('dialect')->current('de'), $username);
        }
    }

    #[Test]
    public function it_uses_the_binding_field_of_the_route()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('posts/hello-world', 'fr'), app('dialect')->translate('post', ['post' => new Post], 'fr'));
    }

    #[Test]
    public function it_translates_routes_inside_a_prefix_group()
    {
        $this->sendRequest('GET', 'admin/guten-abend', 'de');

        $this->assertEquals($this->getUri('admin/good-evening', 'en'), app('dialect')->current('en'));
        $this->assertEquals($this->getUri('admin/good-evening', 'en'), app('dialect')->translate('Tongue::routes.good_evening', null, 'en'));
    }

    #[Test]
    public function it_translates_named_routes_with_a_leading_slash_in_the_translation()
    {
        $this->sendRequest('GET', 'mit-schraegstrich', 'de');

        $this->assertEquals($this->getUri('with-slash', 'en'), app('dialect')->current('en'));
        $this->assertEquals($this->getUri('with-slash', 'en'), app('dialect')->translate('with_slash', [], 'en'));
    }

    #[Test]
    public function it_translates_the_same_key_in_different_prefix_groups()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('with-slash', 'en'), app('dialect')->translate('with_slash', [], 'en'));
        $this->assertEquals($this->getUri('admin/with-slash', 'en'), app('dialect')->translate('admin.with_slash', [], 'en'));

        $this->sendRequest('GET', 'admin/mit-schraegstrich', 'de')->assertOk();

        $this->assertEquals($this->getUri('admin/with-slash', 'en'), app('dialect')->current('en'));
    }

    #[Test]
    public function it_uses_the_binding_field_when_the_route_name_is_the_translation_key()
    {
        $this->setRequestContext('GET', '', 'en');

        $this->assertEquals($this->getUri('artikel/hello-world', 'de'), app('dialect')->translate('Tongue::routes.article', ['post' => new Post], 'de'));
    }

    #[Test]
    public function it_keeps_the_path_of_the_current_request_for_a_route_without_translation()
    {
        $response = $this->sendRequest('GET', 'files/readme', 'de');

        $response->assertOk();
        $this->assertEquals($this->getUri('files/readme', 'fr'), app('dialect')->current('fr'));

        $this->sendRequest('GET', 'files/readme.md', 'de');

        $this->assertEquals($this->getUri('files/readme.md', 'fr'), app('dialect')->current('fr'));
    }

    #[Test]
    public function it_builds_a_route_without_translation_like_laravel()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('files/readme.md', 'fr'), app('dialect')->translate('file', ['base' => 'readme', 'extension' => 'md'], 'fr'));
        $this->assertEquals($this->getUri('blog/2', 'fr'), app('dialect')->translate('blog', ['page' => 2], 'fr'));
    }

    #[Test]
    public function it_accepts_route_attributes_as_collection()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri($this->enPathWithParameter1, 'en'),
            app('dialect')->translate($this->routeNameWithParameter, collect($this->routeParameters), 'en')
        );
    }

    #[Test]
    public function the_route_name_wins_over_another_route_with_the_same_path()
    {
        $this->setRequestContext('GET', 'contact');

        $this->assertEquals($this->getUri('kontakt', 'de'), app('dialect')->translate('Tongue::routes.form', [], 'de'));
        $this->assertEquals($this->getUri('absenden', 'de'), app('dialect')->translate('Tongue::routes.submit', [], 'de'));

        $this->sendRequest('POST', 'contact')->assertOk();

        $this->assertEquals($this->getUri('absenden', 'de'), app('dialect')->current('de'));
    }

    #[Test]
    public function it_keeps_the_base_path_of_an_app_in_a_subfolder()
    {
        // The app runs under https://laraveltongue.dev/shop
        $server = ['SCRIPT_FILENAME' => '/var/www/shop/public/index.php', 'SCRIPT_NAME' => '/shop/index.php'];

        $this->setRequestContext('GET', 'shop/localized', 'en', [], [], [], $server);

        $this->assertEquals('/shop', request()->getBaseUrl());
        $this->assertEquals($this->getUri('shop/privacy', 'de'), app('dialect')->translate('privacy', [], 'de'));
        $this->assertEquals($this->getUri('shop/guten-morgen', 'de'), app('dialect')->translate('Tongue::routes.good_morning', null, 'de'));
        $this->assertEquals($this->getUri('shop', 'de'), app('dialect')->translate('home', [], 'de'));

        $this->sendRequest('GET', 'shop/hallo/samplename', 'de', [], [], [], $server)->assertOk();

        $this->assertEquals($this->getUri('shop/hello/samplename', 'en'), app('dialect')->current('en'));
    }

    #[Test]
    public function it_keeps_the_current_path_when_the_route_is_unknown()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals('https://fr.laraveltongue.dev', app('dialect')->translate('unknown', ['id' => 1], 'fr'));
        $this->assertEquals('https://fr.laraveltongue.dev', app('dialect')->translate(false, null, 'fr'));
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
