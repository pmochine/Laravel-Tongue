<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Foundation\Application;
use Pmochine\LaravelTongue\Localization\Locale;
use Pmochine\LaravelTongue\Localization\Localization;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Misc\ConfigList;

class Tongue
{
    /**
     * The class that handles the locale methods.
     *
     * @var \Pmochine\LaravelTongue\Localization\Locale
     */
    protected $locale;

    public function __construct(Application $app)
    {
        $this->locale = new Locale($app);
    }

    /**
     * Detects the tongue, the locale
     * of the User.
     *
     * @return Tongue :P
     */
    public function detect(): self
    {
        $this->speaks($this->locale->find());

        return $this;
    }

    /**
     * Gets the current speaking tongue...
     * (language code).
     *
     * @return  string|array|null
     */
    public function current($key = null)
    {
        if (! $key) {
            return $this->locale->get();
        }

        return $this->speaking($key, $this->locale->get());
    }

    /**
     * Gets the twist of the tongue.
     * Return the direction left or right.
     * e.g. for arabic language.
     *
     * @return string
     */
    public function leftOrRight(): string
    {
        return $this->locale->scriptDirection($this->current('script'));
    }

    /**
     * A tongue-twister is a phrase that is
     * designed to be difficult to articulate properly,
     * So lets just asume the user just can't speak the
     * language...
     *
     * @return bool (yes if its not speakable)
     */
    public function twister(): bool
    {
        $locale = Localization::fromUrl();

        if ($locale && tongue()->speaking('subdomains', $locale)) {
            //whitelisted subdomains! like admin.domain.com
            return false;
        }

        //custom subdomains with locale. gewinnen.domain.com -> de as locale
        if ($locale && $customLocale = tongue()->speaking('aliases', $locale)) {
            //but we need to check again if it is spoken or not
            return $this->current() !== $customLocale;
        }

        //fallback language is the same as the current language
        if (Config::beautify() && $this->current() === Config::fallbackLocale()) {
            //didn't found locale means browser is set to exmaple.com
            if (! $locale) {
                return false;
            }
            //browser is set to en.example.com but should be forced back to example.com
            if ($locale === Config::fallbackLocale()) {
                return true;
            }
        }

        //decipher from
        return $this->current() !== $locale;
    }

    /**
     * The user speaks locale language.
     * Set the locale.
     *
     * @param  string $locale
     * @return Tongue|\Illuminate\Http\RedirectResponse;
     */
    public function speaks(string $locale)
    {
        if (! $this->isSpeaking($locale)) {
            //locale does not exist.
            return dialect()->redirectBackToLatest();
        }

        $this->locale->save($locale);

        return $this;
    }

    /**
     * Used to return back to previous url.
     * e.g. if you change the language. its usefull.
     *
     * @return \Illuminate\Http\RedirectResponse;
     */
    public function back()
    {
        return dialect()->redirect(dialect()->redirectUrl(url()->previous()));
    }

    /**
     * Gets the collection list of all languages,
     * the website speaks. Or give us the specific keys.
     *
     * @return \Illuminate\Support\Collection|string|array|null
     */
    public function speaking(string $key = null, string $locale = null)
    {
        return (new ConfigList)->lookup($key, $locale);
    }

    /**
     * Checks if your page is speaking the language.
     *
     * @param  string  $locale
     * @return bool
     */
    public function isSpeaking(string $locale): bool
    {
        return $this->speaking()->has($locale);
    }
}
