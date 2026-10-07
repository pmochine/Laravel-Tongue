<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale;
use Pmochine\LaravelTongue\Tests\TestCase;

class MiddlewareAliasTest extends TestCase
{
    #[Test]
    public function it_registers_the_speaks_tongue_middleware_alias()
    {
        $this->assertSame(TongueSpeaksLocale::class, app('router')->getMiddleware()['speaks-tongue'] ?? null);
    }

    #[Test]
    public function the_alias_redirects_like_the_middleware_class()
    {
        app('router')->get('aliased', function () {
            return 'aliased';
        })->middleware('speaks-tongue');

        $this->setRequestContext('GET', 'aliased', null, [], ['tongue-locale' => 'de']);

        $this->call('GET', $this->getUri('aliased'), [], ['tongue-locale' => 'de'])
            ->assertRedirect($this->getUri('aliased', 'de'));
    }
}
