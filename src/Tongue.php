<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Foundation\Application;
use Pmochine\LaravelTongue\Exceptions\SupportedLocalesNotDefined;
use Pmochine\LaravelTongue\Localization\Localization;
use Pmochine\LaravelTongue\Misc\Config;

class Tongue
{
	/**
     * Our instance of the Laravel app
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app = '';

	public function __construct(Application $app)
	{
		$this->app = $app;
	}
	/**
	 * Detects the tongue, the locale
	 * of the User. 
	 * 
	 * @return Tongue :P 
	 */
	public function detect()
	{	
		$locale = $this->findLocale();
		
		$this->speaks($locale);

		return $this;
	}

	/** 
	 * Gets the current speaking tongue... 
	 * (language code)
	 * 
	 * @return  string 
	 */
	public function current($key = null)
	{
		$locale = $this->app->getLocale();

		if(!$key){
			return $locale;
		}

		return $this->speaking($key, $locale);
		
	}

	/**
	 * Gets the twist of the tongue. 
	 * Return the direction left or right. 
	 * e.g. for arabic language
	 * 
	 * @return string 
	 */
	public function leftOrRight()
	{
        switch (Config::supportedLocales()[$this->current()]['script']){
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
	 * A tongue-twister is a phrase that is 
	 * designed to be difficult to articulate properly,
	 * So lets just asume the user just can't speak the
	 * language...
	 * 
	 * @return boolean (yes if its not speakable)
	 */
	public function twister()
	{
		$locale = Localization::fromUrl();

		//fallback language is the same as the current language
		if(Config::beautify() && $this->current() === Config::fallbackLocale()){
			//didn't found locale means browser is set to exmaple.com
			if(!$locale){
				return false;
			}
			//browser is set to en.example.com but should be forced back to example.com
			if($locale === Config::fallbackLocale()){
				return true;
			}
		}

		//decipher from 
		return $this->current() != $locale;
	}

	/**
	 * The user speaks locale language.
	 * Set the locale
	 * 
	 * @param  string $locale 
	 * @return Tongue :P         
	 */
	public function speaks(string $locale)
	{
		if(!$this->isSpeaking($locale)){
			return abort(404); //oder error?
		}

		$this->app->setLocale($this->locale = $locale);

		if ($locale != Localization::cookie() && Config::cookieLocalization()) {
			Localization::cookie($locale);
		}


		// Regional locale such as de_DE, so formatLocalized works in Carbon
        $regional = $this->speaking('regional', $locale);

        if ($regional) {
            setlocale(LC_TIME, $regional.'.UTF-8');
            setlocale(LC_MONETARY, $regional.'.UTF-8');
        }

        return $this;

	}

	/**
	 * Used to return back to previous url.
	 * e.g. if you change the language. its usefull
	 * 
	 * @return Illuminate\Routing\Redirector 
	 */
	public function back()
	{
		return dialect()->redirect( dialect()->redirectUrl( url()->previous() ) );
	}

	/**
	 * Gets the collection list of all languages,
	 * the website speaks. Or give us the specific keys. 
	 * 
	 * @return collection | string
	 */
	public function speaking($key = null, $locale = null)
	{
		$locales = Config::supportedLocales();

		if (empty($locales) || !is_array($locales)) {
		    throw new SupportedLocalesNotDefined();
		}

		if(!$key){
	        return collect($locales);
		}

		if(!array_has($locales, "{$locale}.{$key}")){
			throw new SupportedLocalesNotDefined();
		}

		return data_get($locales, "{$locale}.{$key}");
		
	}

	/**
	 * Finds the Subdomain in the URL. 
	 * Like en, de...
	 * 
	 * @return string 
	 */
	protected function findLocale()
	{
		if(!Config::subdomain()){
			return false; //use Mcamara Localization
		}

		return Localization::decipherTongue();
	}

	/**
	 * Checks if your page is speaking the language.
	 * 
	 * @param  string  $locale 
	 * @return boolean         
	 */
	protected function isSpeaking($locale)
	{
		return array_key_exists($locale, Config::supportedLocales());
	}


}
