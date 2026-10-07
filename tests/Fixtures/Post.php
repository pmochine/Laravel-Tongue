<?php

namespace Pmochine\LaravelTongue\Tests\Fixtures;

use Illuminate\Contracts\Routing\UrlRoutable;

/**
 * A model for route model binding: the route key is the id, the slug is another binding field.
 */
class Post implements UrlRoutable
{
    public $id = 123;

    public $slug = 'hello-world';

    public function getRouteKey()
    {
        return $this->id;
    }

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
