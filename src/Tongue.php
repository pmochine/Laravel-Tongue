<?php

namespace Pmochine\LaravelTongue;

use Pmochine\LaravelTongue\Localization\Localization;
use Pmochine\LaravelTongue\Misc\Config;
use Illuminate\Foundation\Application;

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
	public function current()
	{
		return $this->app->getLocale();
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
        $regional = $this->regional($locale);

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

	/**
     * Returns current regional.
     *
     * @return string current regional
     */
    protected function regional($locale)
    {
        // need to check if it exists, since 'regional' has been added
        // after version 1.0.11 and existing users will not have it
   
        if (isset(Config::supportedLocales()[$locale]['regional'])) {
            return Config::supportedLocales()[$locale]['regional'];
        } 
            
        return;
    }
}
