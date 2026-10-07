<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Dialect;
use Pmochine\LaravelTongue\Facades\Dialect as DialectFacade;
use Pmochine\LaravelTongue\Facades\Tongue as TongueFacade;
use Pmochine\LaravelTongue\Tests\TestCase;
use Pmochine\LaravelTongue\Tongue;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function the_helpers_the_facades_and_the_container_share_one_instance()
    {
        $this->assertInstanceOf(Tongue::class, tongue());
        $this->assertSame(tongue(), tongue());
        $this->assertSame(tongue(), app('tongue'));
        $this->assertSame(tongue(), TongueFacade::getFacadeRoot());

        $this->assertInstanceOf(Dialect::class, dialect());
        $this->assertSame(dialect(), dialect());
        $this->assertSame(dialect(), app('dialect'));
        $this->assertSame(dialect(), DialectFacade::getFacadeRoot());
    }

    #[Test]
    public function a_route_interpreted_with_the_helper_is_known_to_the_facade()
    {
        app('translator')->getLoader()->addNamespace('Tongue', dirname(__DIR__).'/lang');
        app()->setLocale('de');

        dialect()->interpret('Tongue::routes.good_morning');

        $this->assertSame('Tongue::routes.good_morning', DialectFacade::findRouteNameByPath('guten-morgen'));
    }

    #[Test]
    public function tongue_is_built_again_for_each_octane_request_but_dialect_keeps_its_routes()
    {
        $this->setRequestContext('GET', '', 'de');
        $tongue = app('tongue');
        dialect()->interpret('Tongue::routes.good_morning');

        // Octane clones the application for each request and flushes the scoped instances.
        $sandbox = clone $this->app;
        $sandbox->instance('app', $sandbox);
        $sandbox->instance('config', clone $this->app['config']);
        Container::setInstance($sandbox);
        $sandbox->forgetScopedInstances();

        try {
            $this->assertNotSame($tongue, $sandbox->make('tongue'));

            $sandbox->make('tongue')->speaks('fr');

            $this->assertSame('fr', $sandbox['config']->get('app.locale'));
            $this->assertSame('de', $this->app['config']->get('app.locale'));

            $this->assertSame('Tongue::routes.good_morning', $sandbox->make('dialect')->findRouteNameByPath('guten-morgen'));
        } finally {
            Container::setInstance($this->app);
        }
    }
}
