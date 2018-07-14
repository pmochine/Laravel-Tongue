<?php

namespace Pmochine\LaravelTongue\Tests;

use Pmochine\LaravelTongue\Facades\Tongue;
use Pmochine\LaravelTongue\ServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelTongueTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'tongue' => Tongue::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
