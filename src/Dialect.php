<?php

namespace Pmochine\LaravelTongue;

use Illuminate\Foundation\Application;
use Pmochine\LaravelTongue\Accent\Accent;
use Pmochine\LaravelTongue\Localization\Localization;
use Pmochine\LaravelTongue\Misc\Config;
use Illuminate\Http\RedirectResponse;

/**
 * This class was written by 
 * https://github.com/hoyvoy/laravel-subdomain-localization
 * Now is the time to have my own dialect with it :P
 */
class Dialect
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
	 * Adds the detected locale to the current unlocalized URL.
	 * 
	 * @return string 
	 */
	public function redirectUrl($url = null)
	{
		
		$parsed_url = parse_url($url ?? request()->fullUrl() );

		$domain = request()->server("SERVER_NAME");

		if(Config::beautify() && Localization::fromUrl() === Config::fallbackLocale()){
			$parsed_url['host'] = $domain;
		} else {
			$parsed_url['host'] = tongue()->current().'.'.$domain;
		}

		return Accent::unparseUrl($parsed_url);
	}
	/**
	 * Creates the redirect response
	 * 
	 * @param   string 
	 * @return Illuminate\Http\RedirectResponse;
	 */
	public function redirect($redirection)
	{
		// Save any flashed data for redirect
		app('session')->reflash();
		
		return new RedirectResponse($redirection, 302, ['Vary' => 'Accept-Language']);
	}

}