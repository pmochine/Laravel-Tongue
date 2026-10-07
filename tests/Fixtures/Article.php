<?php

namespace Pmochine\LaravelTongue\Tests\Fixtures;

use Illuminate\Contracts\Routing\UrlRoutable;
use Pmochine\LaravelTongue\Contracts\LocalizedUrlRoutable;

/**
 * A model with a translated slug for each locale.
 */
class Article implements LocalizedUrlRoutable, UrlRoutable
{
    public const SLUGS = ['en' => 'important-change', 'de' => 'wichtige-aenderung', 'hu' => null];

    public function getLocalizedRouteKey(string $locale)
    {
        // Hungarian has no slug yet
        return array_key_exists($locale, self::SLUGS) ? self::SLUGS[$locale] : self::SLUGS['en'];
    }

    public function getRouteKey()
    {
        return $this->getLocalizedRouteKey(app()->getLocale()) ?? self::SLUGS['en'];
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return in_array($value, array_filter(self::SLUGS), true) ? new self : null;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
