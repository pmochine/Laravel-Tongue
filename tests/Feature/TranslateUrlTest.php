<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\TestCase;

class TranslateUrlTest extends TestCase
{
    #[Test]
    public function it_translates_the_path_of_a_url()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('hello/samplename', 'en'), app('dialect')->translateUrl($this->getUri('hallo/samplename', 'de'), 'en'));
        $this->assertEquals($this->getUri('artikel/wichtige-aenderung', 'de'), app('dialect')->translateUrl($this->getUri('artikel/wichtige-aenderung', 'de')));
    }

    #[Test]
    public function it_keeps_the_query_string_and_the_fragment()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri('good-morning?page=2#top', 'en'),
            app('dialect')->translateUrl($this->getUri('guten-morgen?page=2#top', 'de'), 'en')
        );

        // The order of the query string stays
        $this->assertEquals(
            $this->getUri('good-morning?z=1&a=2', 'en'),
            app('dialect')->translateUrl($this->getUri('guten-morgen?z=1&a=2', 'de'), 'en')
        );
    }

    #[Test]
    public function it_translates_a_relative_url()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('hello/samplename', 'en'), app('dialect')->translateUrl('/hallo/samplename', 'en'));
    }

    #[Test]
    public function it_only_changes_the_subdomain_of_a_url_without_translation()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('localized?x=1', 'fr'), app('dialect')->translateUrl($this->getUri('localized?x=1', 'de'), 'fr'));
        $this->assertEquals($this->getUri('no/route/here', 'fr'), app('dialect')->translateUrl($this->getUri('no/route/here', 'de'), 'fr'));
    }

    #[Test]
    public function it_translates_the_slug_of_a_url()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals(
            $this->getUri('article/important-change', 'en'),
            app('dialect')->translateUrl($this->getUri('artikel/wichtige-aenderung', 'de'), 'en')
        );
    }

    #[Test]
    public function it_keeps_the_parameters_of_the_current_route()
    {
        $this->sendRequest('GET', 'hallo/samplename', 'de')->assertOk();

        $this->assertEquals($this->getUri('hello/other', 'en'), app('dialect')->translateUrl($this->getUri('hallo/other', 'de'), 'en'));
        $this->assertSame('samplename', app('router')->current()->parameter('username'));
    }

    #[Test]
    public function back_translates_the_previous_page()
    {
        // The fallback locale "en" goes to the beautiful URL. The cookie keeps the new locale.
        $response = $this->sendRequest('POST', 'switch/en', 'de', [], [], [], ['HTTP_REFERER' => $this->getUri('hallo/samplename', 'de')]);

        $response->assertRedirect($this->getUri('hello/samplename'));
        $this->assertTrue($this->responseHasCookies($response, ['tongue-locale' => 'en']));

        $response = $this->sendRequest('POST', 'switch/de', 'en', [], [], [], ['HTTP_REFERER' => $this->getUri('hello/samplename', 'en')]);

        $response->assertRedirect($this->getUri('hallo/samplename', 'de'));
    }
}
