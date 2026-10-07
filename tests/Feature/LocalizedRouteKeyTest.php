<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Tests\Fixtures\Article;
use Pmochine\LaravelTongue\Tests\TestCase;

/**
 * Models that implement LocalizedUrlRoutable get their route key in the target locale.
 */
class LocalizedRouteKeyTest extends TestCase
{
    #[Test]
    public function the_current_url_gets_the_translated_slug_of_a_translated_route()
    {
        $this->sendRequest('GET', 'artikel/wichtige-aenderung', 'de')->assertOk();

        $this->assertEquals($this->getUri('article/important-change', 'en'), app('dialect')->current('en'));
        $this->assertEquals($this->getUri('artikel/wichtige-aenderung', 'de'), app('dialect')->current('de'));

        // French has no translation, so it uses the English path and slug
        $this->assertEquals($this->getUri('article/important-change', 'fr'), app('dialect')->translateAll()['fr']);
    }

    #[Test]
    public function the_current_url_gets_the_translated_slug_of_a_route_without_translation()
    {
        $this->sendRequest('GET', 'news/wichtige-aenderung', 'de')->assertOk();

        $this->assertEquals($this->getUri('news/important-change', 'en'), app('dialect')->current('en'));
    }

    #[Test]
    public function translate_uses_the_translated_slug_of_an_attribute()
    {
        $this->setRequestContext('GET', '', 'de');

        $this->assertEquals($this->getUri('article/important-change', 'en'), app('dialect')->translate('Tongue::routes.article_slug', ['article' => new Article], 'en'));
        $this->assertEquals($this->getUri('artikel/wichtige-aenderung', 'de'), app('dialect')->translate('Tongue::routes.article_slug', ['article' => new Article]));
        $this->assertEquals($this->getUri('news/important-change', 'en'), app('dialect')->translate('news', ['article' => new Article], 'en'));
    }

    #[Test]
    public function the_translated_slug_leads_to_the_article()
    {
        $this->sendRequest('GET', 'artikel/wichtige-aenderung', 'de');

        $this->sendRequest('GET', 'article/important-change', 'fr')->assertOk();
    }
}
