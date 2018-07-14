<?php

namespace Pmochine\LaravelTongue\Tests;

use Pmochine\LaravelTongue\Facades\LaravelTongue;
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
            'laravel-tongue' => LaravelTongue::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
