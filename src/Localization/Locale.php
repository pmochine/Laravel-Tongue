<?php

namespace Pmochine\LaravelTongue\Localization;

use Pmochine\LaravelTongue\Misc\Config;

/**
 * Uses app() for each call and keeps no instance of the app.
 * In Octane, app() is the application of the current request.
 */
class Locale
{
    /**
     * Gets the current app locale that is set.
     *
     * @return string
     */
    public function get(): string
    {
        return app()->getLocale();
    }

    /**
     * Sets the locale in the app.
     *
     * @param  string  $locale
     * @return void
     */
    public function set(string $locale): void
    {
        app()->setLocale($locale);
    }

    /**
     * Set and saves locale in app & cookies and sets regions.
     *
     * @param  string  $locale
     * @return void
     */
    public function save(string $locale): void
    {
        $this->set($locale);
        $this->saveInCookie($locale);
        $this->setRegionalTimeAndMoney($locale);
    }

    /**
     * Save locale in cookie.
     *
     * @param  string  $locale
     * @return void
     */
    public function saveInCookie(string $locale): void
    {
        if (Config::cookieLocalization()) {
            Localization::cookie($locale);
        }
    }

    public function setRegionalTimeAndMoney(string $locale): void
    {
        // Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = tongue()->speaking('regional', $locale);

        if ($regional && is_string($regional)) {
            setlocale(LC_TIME, $regional.'.UTF-8');
            setlocale(LC_MONETARY, $regional.'.UTF-8');
        }
    }

    /**
     * Gets the twist of the tongue.
     * Return the direction left or right.
     * e.g. for arabic language.
     *
     * @param  string  $script
     * @return string
     */
    public function scriptDirection(string $script): string
    {
        switch ($script) {
            case 'Arab':
            case 'Hebr':
            case 'Mong':
            case 'Tfng':
            case 'Thaa':
                return 'rtl';
            default:
                return 'ltr';
        }
    }

    /**
     * Finds the locale in the URL.
     * Like en, de...
     *
     * @return string
     */
    public function find(): string
    {
        return Localization::decipherTongue();
    }
}
