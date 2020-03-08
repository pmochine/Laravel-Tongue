<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Pmochine\LaravelTongue\Exceptions\SupportedLocalesNotDefined;
use Pmochine\LaravelTongue\ServiceProvider;
use Pmochine\LaravelTongue\Tests\TestCase;

class TongueTest extends TestCase
{
    /**
     * Get package providers. To read the config file.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
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

        $this->assertEquals(app('tongue')->current('BCP47'), 'de-DE');
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

        $this->assertEquals('ltr', app('tongue')->leftOrRight());
    }

    /**
     * Important to note. I just don't want to test the full array...
     * @test
     */
    public function it_returns_the_available_locales()
    {
        $supportedLocales = ['en', 'es', 'fr', 'de'];

        app('config')->set('localization.supportedLocales', $supportedLocales);

        $this->assertEquals($supportedLocales, app('tongue')->speaking()->all());
    }

    /** @test */
    public function it_returns_the_subdomains_and_can_validate_it()
    {
        app('config')->set('localization.subdomains', $subdomains = ['admin']);

        $this->assertEquals($subdomains, app('tongue')->speaking('subdomains'));

        $this->assertTrue(app('tongue')->speaking('subdomains', 'admin'));
        $this->assertFalse(app('tongue')->speaking('subdomains', 'blubb'));
    }

    /** @test */
    public function it_returns_the_aliases()
    {
        app('config')->set('localization.aliases', $subdomains = ['gewinnen' => 'de']);

        $this->assertEquals($subdomains, app('tongue')->speaking('aliases'));

        $this->assertEquals('de', app('tongue')->speaking('aliases', 'gewinnen'));
    }
}
