<?php

namespace Pmochine\LaravelTongue\Facades;

use Illuminate\Support\Facades\Facade;

class Tongue extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tongue';
    }
}
