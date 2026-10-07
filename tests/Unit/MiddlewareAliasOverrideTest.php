<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale;
use Pmochine\LaravelTongue\Tests\TestCase;

class MiddlewareAliasOverrideTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        // The app defines its own class for the alias before the package boots.
        $app['router']->aliasMiddleware('speaks-tongue', CustomSpeaksLocale::class);
    }

    #[Test]
    public function it_keeps_an_alias_that_the_app_defines()
    {
        $this->assertSame(CustomSpeaksLocale::class, app('router')->getMiddleware()['speaks-tongue']);
    }
}

class CustomSpeaksLocale extends TongueSpeaksLocale
{
}
