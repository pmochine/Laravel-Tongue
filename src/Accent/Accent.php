<?php

namespace Pmochine\LaravelTongue\Accent;

class Accent
{
	/**
	 * Get url using array data from parse_url.
	 *
	 * @param array|false $parsed_url Array of data from parse_url function
	 *
	 * @return string Returns URL as string.
	 */
	public static function unparseUrl($parsed_url)
	{
	    if (empty($parsed_url)) {
	        return '';
	    }
	    $url = '';
	    $url .= isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
	    $url .= isset($parsed_url['host']) ? $parsed_url['host'] : '';
	    $url .= isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
	    $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	    $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
	    $url .= $user.(($user || $pass) ? "$pass@" : '');
	    if (!empty($url)) {
	        $url .= isset($parsed_url['path']) ? '/'.ltrim($parsed_url['path'], '/') : '';
	    } elseif (empty($url)) {
	        $url .= isset($parsed_url['path']) ? $parsed_url['path'] : '';
	    }
	    $url .= isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
	    $url .= isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';

	    return $url;
	}
}