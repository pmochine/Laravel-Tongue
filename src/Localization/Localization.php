<?php

namespace Pmochine\LaravelTongue\Localization;

use Pmochine\LaravelTongue\Localization\TongueDetector;
use Pmochine\LaravelTongue\Misc\Config;
use Illuminate\Contracts\Encryption\DecryptException;

class Localization 
{
	/** Name of the cookie */
	const COOKIE = 'tongue-locale';
	/**
	 * Finds the locale of the domain. 
	 * 
	 * @return stirng|exception [Finds the locale of the url, throws exception if wrong]
	 */
	public static function decipherTongue()
	{
		$locale = self::fromUrl();

		if(!$locale){
			if(!Config::beautify()){
				return; //redirect?? or exception?
			}
			$locale = self::currentTongue();
		}

		return $locale;
	}

	/**
	 * Gets the locale from the url.
	 * @return string|boolean false [when domain===locale]
	 */
	public static function fromUrl()
	{
		$domain = explode('.', self::domain())[0];
		$locale = explode('.', request()->getHost())[0];

		return $domain === $locale ? false : $locale;
	}

	/**
	 * Tries to get the current locale of the user. 
	 * Via the Browser or the fallback language
	 * 
	 * @return string 
	 */
	protected static function currentTongue()
	{
		if(Config::cookieLocalization() && $locale = self::cookie()){
			return $locale;
		}

		if (Config::acceptLanguage() && !app()->runningInConsole()) {

            $detector = new TongueDetector(Config::fallbackLocale(), Config::supportedLocales(), request());

            return $detector->negotiateLanguage();
		}

		return Config::fallbackLocale();
	}
	/**
	 * Gets the domain of the website.
	 * @return string
	 */
	protected static function domain()
	{
		return request()->server("SERVER_NAME");
	}

	/**
	 * Sets or gets the cookie of the locale.
	 * Important to set config/session domain to .exmaple.com
	 * https://gistlog.co/JacobBennett/15558410de2a394373ac
	 * 
	 * @param  string $locale 
	 * @return string|null       
	 */
	public static function cookie($locale = null)
	{
		if($locale == null){
			try {
			    return decrypt(request()->cookie(self::COOKIE));
			} catch (DecryptException $e) {
			    //Somehow the middleware for decrypting does not kick in here... 
			    //but it even fails if we use php artisan <something> (weird)
			    //if it happes we can simply give it normally back
			    return request()->cookie(self::COOKIE);
			}			
		}
		cookie()->queue(cookie()->forever(self::COOKIE, $locale));
	}


	
}