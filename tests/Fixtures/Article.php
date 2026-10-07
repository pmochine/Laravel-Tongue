<?php

namespace Pmochine\LaravelTongue\Tests\Fixtures;

use Illuminate\Contracts\Routing\UrlRoutable;
use Pmochine\LaravelTongue\Contracts\LocalizedUrlRoutable;

/**
 * A model with a translated slug for each locale.
 */
class Article implements LocalizedUrlRoutable, UrlRoutable
{
    public const SLUGS = ['en' => 'important-change', 'de' => 'wichtige-aenderung'];

    public function getLocalizedRouteKey(string $locale)
    {
        return self::SLUGS[$locale] ?? self::SLUGS['en'];
    }

    public function getRouteKey()
    {
        return $this->getLocalizedRouteKey(app()->getLocale());
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return in_array($value, self::SLUGS, true) ? new self : null;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
