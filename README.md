
# Laravel Tongue 👅 - Multilingual subdomain urls and redirects


[![Build Status](https://travis-ci.org/pmochine/laravel-tongue.svg?branch=master)](https://travis-ci.org/pmochine/laravel-tongue)
[![styleci](https://styleci.io/repos/140954300/shield)](https://styleci.io/repos/140954300)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/CHANGEME/mini.png)](https://insight.sensiolabs.com/projects/CHANGEME)
[![Coverage Status](https://coveralls.io/repos/github/pmochine/laravel-tongue/badge.svg?branch=master)](https://coveralls.io/github/pmochine/laravel-tongue?branch=master)

[![Packagist](https://img.shields.io/packagist/v/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://poser.pugx.org/pmochine/laravel-tongue/d/total.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://img.shields.io/packagist/l/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)

![Laravel Tongue](img/laravel-tongue.png)

**If you are looking for an easy package for subdomain multilingual urls, this package is for you.  😜**

## Installation in 4 Steps*

### 1: Add with composer 💻
```bash
composer require pmochine/laravel-tongue
```

### 2: Publish Configuration File (you need to change some thingys so use it 😎)

```bash
php artisan vendor:publish --provider="Pmochine\LaravelTongue\ServiceProvider" --tag="config"
```
### 3: Add the Middleware 🌐
**Laravel Tongue** comes with a middleware that can be used to enforce the use of a language subdomain. For example: the user calls example.com it goes directly to fr.example.com. 

If you want to use it, open `app/Http/kernel.php` and register this route middleware by adding it to the `routeMiddleware` (down below) array:

```php
	...
    'speaks-tongue' => \Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale::class,
	...
```

### 4: Add in your Env 🔑

    SESSION_DOMAIN=.exmaple.com
    
  **Important!** Note the dot before the domain name. Now the session is availabe in every subdomain 🙃. 



### *Note! 📝 This step is optional if you use laravel>=5.5 with package auto discovery feature.

Add service provider to `config/app.php` in `providers` section
```php
Pmochine\LaravelTongue\ServiceProvider::class,
```

## Usage - (or to make it runnable 🏃‍♂️)


### Locale detection 🔍

Open `app/Providers/RouteServiceProvider.php` and add this

```php
    public function boot()
    {
        // This will guess a locale from the current HTTP request
        // and set the application locale.
        tongue()->detect();
        
        parent::boot();
    }
	...
```

Once you have done this, there is nothing more that you MUST do. Laravel application locale has been set and you can use other locale-dependant Laravel components (e.g. Translation) as you normally do.

### Middleware 🌐

If you want to enforce the use of a language subdomain for some routes, you can simply assign the middleware provided, for example as follows in `routes/web.php`:

```php
    // Without the localize middleware, this route can be reached with or without language subdomain
    Route::get('logout', 'AuthController@logout');
    
    // With the localize middleware, this route cannot be reached without language subdomain
    Route::group([ 'middleware' => [ 'speaks-tongue' ]], function() {
    
        Route::get('welcome', 'WelcomeController@index');
    
    });
```

For more information about Middleware, please refer to <a href="http://laravel.com/docs/middleware">Laravel docs</a>.

### Frontend 😴

```php
<!doctype html>
<html lang="{{tongue()->current()}}" dir="{{tongue()->leftOrRight()}}">

	<head>
	  	@include('layouts.head')
  	</head>

	<body>
	...
```
The above `<html>` tag will always have a supported locale and directionality (‘ltr’ or ‘rtl’). The latter is important for right-to-left languages like Arabic and Hebrew, since the whole page layout will change for those.

### How to Switch Up the Language 🇬🇧->🇩🇪
In a controller far far away...

 ```php
    /**
     * Sets the locale in the app
     * @return redirect to previous url
     */
    public function store()
    {
    	$locale = request()->validate([
    		'locale' => 'required|string|size:2'
    	])['locale'];

    	return tongue()->speaks($locale)->back();
    } 
  ```

## Configuration

Once you have imported the config file, you will find it at `config/localization.php`.

### Configuration values

- `beautify_url` (default: `true`)

Makes the URL BEAUTIFUL 💁‍♀️. ( Use to set fallback language to mydomain.com and not to en.mydomain.com). That is why I even created this package. I just could not find this! 😭

- `subdomain` (default: `true`)

Just don't dare to change this value 🙅‍♂️. (It's not implemented, but we could use example.com/en, but wth just use the packages down below)

- `acceptLanguage` (default: `true`)

Use this option to enable or disable the use of the browser 💻 settings during the locale detection.

- `cookie_localization` (default: `true`)

Use this option to enable or disable the use of cookies 🍪 during the locale detection.

- `supportedLocales` (default: `🇬🇧🇩🇪🇪🇸🇫🇷🇭🇺`)

Don't say anyone that I copied it from [mcamara](https://github.com/mcamara/laravel-localization) 🤫


## TODO
Yeah yeah still need to do some little things. So I'm going to update. Use it if you are not AFRAID 🤓.

## Security

If you discover any security related issues, please don't email me. I'm afraid 😱. avidofood@protonmail.com

## Credits

Now comes the best part! 😍
This package is based on

 - https://github.com/hoyvoy/laravel-subdomain-localization
 - https://github.com/mcamara/laravel-localization

Oh come on. You read everything?? If you liked it so far, hit the ⭐️ button to give me a 🤩 face. 
