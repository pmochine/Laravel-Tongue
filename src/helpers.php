<?php

use Pmochine\LaravelTongue\Tongue;
use Pmochine\LaravelTongue\Dialect;

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
