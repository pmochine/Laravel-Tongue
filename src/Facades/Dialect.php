<?php

namespace Pmochine\LaravelTongue\Facades;

use Illuminate\Support\Facades\Facade;

class Dialect extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dialect';
    }
}
