<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

/**
 * dialect()->alternates() gives the URLs for <link rel="alternate" hreflang="...">.
 */
class AlternatesTest extends TestCase
{
    #[Test]
    public function it_lists_every_locale_and_x_default_with_the_url_without_redirect()
    {
        $this->sendRequest('GET', 'guten-morgen', 'de')->assertOk();

        $this->assertEquals([
            'de' => $this->getUri('guten-morgen', 'de'),
            // The fallback locale has the beautiful URL. The middleware does not redirect it.
            'en' => $this->getUri('good-morning'),
            'es' => $this->getUri('good-morning', 'es'),
            'fr' => $this->getUri('good-morning', 'fr'),
            'hu' => $this->getUri('good-morning', 'hu'),
            'x-default' => $this->getUri('good-morning'),
        ], app('dialect')->alternates());

        // The language switcher keeps the subdomain, so the cookie switches before the redirect
        $this->assertEquals($this->getUri('good-morning', 'en'), app('dialect')->current('en'));
    }

    #[Test]
    public function it_uses_the_subdomain_of_the_fallback_locale_without_beautify_url()
    {
        app('config')->set('localization.beautify_url', false);

        $this->sendRequest('GET', 'localized', 'de')->assertOk();

        $this->assertEquals($this->getUri('localized', 'en'), app('dialect')->alternates()['en']);
        $this->assertEquals($this->getUri('localized', 'en'), app('dialect')->alternates()['x-default']);
    }

    #[Test]
    public function it_uses_the_aliases_with_alias_urls()
    {
        app('config')->set('localization.alias_urls', true);
        app('config')->set('localization.aliases', ['gewinnen' => 'de']);

        $this->sendRequest('GET', 'localized', 'gewinnen')->assertOk();

        $this->assertEquals($this->getUri('localized', 'gewinnen'), app('dialect')->alternates()['de']);
    }

    #[Test]
    public function it_writes_the_hreflang_with_a_hyphen()
    {
        app('config')->set('localization.supportedLocales', [
            'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
            'pt_BR' => ['name' => 'Brazilian Portuguese', 'script' => 'Latn', 'native' => 'português do Brasil', 'regional' => 'pt_BR'],
        ]);

        $this->sendRequest('GET', 'localized')->assertOk();

        $this->assertEquals(['en', 'pt-BR', 'x-default'], array_keys(app('dialect')->alternates()));
    }

    #[Test]
    public function it_gives_the_translated_slugs()
    {
        $this->sendRequest('GET', 'artikel/wichtige-aenderung', 'de')->assertOk();

        $this->assertEquals($this->getUri('article/important-change'), app('dialect')->alternates()['en']);
    }
}
