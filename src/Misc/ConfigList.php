<?php

namespace Pmochine\LaravelTongue\Misc;

use Illuminate\Support\Arr;
use Pmochine\LaravelTongue\Exceptions\SupportedLocalesNotDefined;

class ConfigList
{
    public function lookup(string $key = null, string $locale = null)
    {
        $locales = Config::supportedLocales();

        if (empty($locales) || ! is_array($locales)) {
            throw new SupportedLocalesNotDefined();
        }

        if (! $key) {
            return collect($locales);
        }

        if ($key === 'BCP47') {
            return $this->BCP47($locale, $locales);
        }

        if ($key === 'subdomains') {
            return $this->getSubdomains($locale);
        }

        if ($key === 'aliases') {
            return $this->getAliases($locale);
        }

        if (! Arr::has($locales, "{$locale}.{$key}")) {
            throw new SupportedLocalesNotDefined();
        }

        return data_get($locales, "{$locale}.{$key}");
    }

    /**
     * Gets the BCP 47 Value of the regional
     * See for more: http://schneegans.de/lv/?tags=en&format=text.
     *
     * @param  string|null $locale
     * @param  array $loacles [the list in the config file]
     * @return string|null
     */
    protected function BCP47(string $locale = null, array $locales): ?string
    {
        $bcp47 = data_get($locales, "{$locale}.regional");

        if (! $bcp47) {
            return $locale;
        } //locale is the "minimum" of BCP 47

        //regional value needs to replace underscore
        return str_replace('_', '-', $bcp47);
    }

    /**
     * @param   string  $subdomain  [like "admin"]
     *
     * @return  array|bool
     */
    protected function getSubdomains(string $subdomain = null)
    {
        if (is_null($subdomain)) {
            return Config::subdomains();
        }

        return in_array($subdomain, Config::subdomains());
    }

    /**
     * Gets the array of the config, or gets the locale value of a subdomain.
     * Like: "gewinnen" -> "de".
     *
     * @param   string  $subdomain
     *
     * @return  array|string
     */
    protected function getAliases(string $subdomain = null)
    {
        $domains = Config::aliases();

        if (is_null($subdomain)) {
            return $domains;
        }

        if (array_key_exists($subdomain, $domains)) {
            return $domains[$subdomain];
        }

        return '';
    }
}
