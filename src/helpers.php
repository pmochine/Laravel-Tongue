<?php

use Pmochine\LaravelTongue\Dialect;
use Pmochine\LaravelTongue\Tongue;

if (! function_exists('tongue')) {
    function tongue()
    {
        return app()->make(Tongue::class);
    }
}
if (! function_exists('dialect')) {
    function dialect()
    {
        return app()->make(Dialect::class);
    }
}
