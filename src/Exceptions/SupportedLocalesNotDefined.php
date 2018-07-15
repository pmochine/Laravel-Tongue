<?php

namespace Pmochine\LaravelTongue\Exceptions;

use Exception;

class SupportedLocalesNotDefined extends Exception
{
    public function __construct()
    {
        parent::__construct('Supported locales must be properly defined.');
    }
}
