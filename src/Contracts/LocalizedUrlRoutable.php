<?php

namespace Pmochine\LaravelTongue\Contracts;

/**
 * A model with a route key per locale, like a translated slug:
 * en.example.com/article/important-change and de.example.com/artikel/wichtige-aenderung.
 */
interface LocalizedUrlRoutable
{
    /**
     * The route key of the model in the given locale.
     *
     * @param  string  $locale  [like "de"]
     * @return string|int
     */
    public function getLocalizedRouteKey(string $locale);
}
