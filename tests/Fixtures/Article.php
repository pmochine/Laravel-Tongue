<?php

namespace Pmochine\LaravelTongue\Tests\Fixtures;

use Illuminate\Contracts\Routing\UrlRoutable;
use Pmochine\LaravelTongue\Contracts\LocalizedUrlRoutable;

/**
 * A model with a translated slug for each locale.
 */
class Article implements LocalizedUrlRoutable, UrlRoutable
{
    public const SLUGS = ['en' => 'important-change', 'de' => 'wichtige-aenderung', 'es' => 'cambio-importante', 'hu' => null];

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

    // Like the README: the slug of the current locale finds the article
    public function resolveRouteBinding($value, $field = null)
    {
        return $value === $this->getRouteKey() ? new self : null;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
