<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Middleware\TongueDetectsLocale;
use Pmochine\LaravelTongue\Tests\TestCase;

class DetectsLocaleMiddlewareTest extends TestCase
{
    protected function defineRoutes($router)
    {
        // No detection in a service provider, like in an Octane app
        $router->get('detected', function () {
            return app()->getLocale();
        })->middleware('detects-tongue');
    }

    #[Test]
    public function it_registers_the_detects_tongue_middleware_alias()
    {
        $this->assertSame(TongueDetectsLocale::class, app('router')->getMiddleware()['detects-tongue'] ?? null);
    }

    #[Test]
    public function it_detects_the_locale_for_each_request()
    {
        $this->call('GET', $this->getUri('detected', 'de'))->assertSee('de');

        $this->call('GET', $this->getUri('detected'), [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr'])->assertSee('fr');

        $this->call('GET', $this->getUri('detected', 'hu'))->assertSee('hu');
    }
}
