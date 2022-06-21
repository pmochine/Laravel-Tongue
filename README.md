


# Laravel Tongue 👅 - Multilingual subdomain URLs and redirects


[![Build Status](https://travis-ci.org/pmochine/Laravel-Tongue.svg?branch=master)](https://travis-ci.org/pmochine/Laravel-Tongue)
[![styleci](https://styleci.io/repos/140954300/shield)](https://styleci.io/repos/140954300)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/pmochine/Laravel-Tongue/badge.svg?branch=master)](https://coveralls.io/github/pmochine/Laravel-Tongue?branch=master)

[![Packagist](https://img.shields.io/packagist/v/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://poser.pugx.org/pmochine/laravel-tongue/d/total.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://img.shields.io/packagist/l/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)

![Laravel Tongue](img/laravel-tongue.png)

**If you are looking for an easy package for subdomain multilingual URLs, this package is for you.  😜**

 **Old Way**: `https://example.com/de`, `https://example.com/fr` etc. <br>
 **New Way**: `https://de.example.com`, `https://fr.example.com` etc.

 >***Prerequisites**: PHP ^7.4 || ^8.0  and Laravel ^8.41 || PHP ^8.0.2  and Laravel ^9.0
 >***Older Laravel Versions**: [Click here](https://github.com/pmochine/Laravel-Tongue#support-for-laravel-5xx)

## Installation in 4 Steps*

### 1: Add with composer 💻
```bash
  composer require pmochine/laravel-tongue
```

### 2: Publish Configuration File (you need to change some things to use it 😎)

```bash
  php artisan vendor:publish --provider="Pmochine\LaravelTongue\ServiceProvider" --tag="config"
```
### 3: Add the Middleware 🌐
**Laravel Tongue** comes with a middleware that can be used to enforce the use of a language subdomain. For example the user calls example.com it goes directly to fr.example.com. 

If you want to use it, open `app/Http/kernel.php` and register this route middleware by adding it to the `routeMiddleware` (down below) array:

```php
  ...
  'speaks-tongue' => \Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale::class,
  ...
```

### 4: Add in your Env 🔑
```shell
  APP_DOMAIN=yourdomain.com #Only important for domains with many dots like: '155ad73e.eu.ngrok.io'
  SESSION_DOMAIN=.yourdomain.com #Read down below why
```
  **Important!** Note the dot before the domain name. Now the session is available in every subdomain 🙃. This is important because you want to save all your cookie 🍪 data in one place and not in many other.



> ****Note*!** 📝 This step is optional if you use laravel>=5.5 with package auto-discovery feature.
> Add service provider to `config/app.php` in `providers` section
>```php
>    Pmochine\LaravelTongue\ServiceProvider::class,
>```

## Usage - (or to make it runnable 🏃‍♂️)


### Locale detection 🔍

Open `app/Providers/RouteServiceProvider.php` and add this

```php
  public function boot()
  {
      // This will guess a locale from the current HTTP request
      // and set the application locale.
      tongue()->detect();
      
      //If you use Carbon you can set the Locale right here.
      \Carbon\Carbon::setLocale(tongue()->current()); 
      
      parent::boot();
  }
  ...
```

Once you have done this, there is nothing more that you MUST do. Laravel application locale has been set and you can use other locale-dependent Laravel components (e.g. Translation) as you normally do.

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
The above `<html>` tag will always have a supported locale and directionality (‘ltr’ or ‘rtl’). The latter is important for right-to-left languages like Arabic and Hebrew since the whole page layout will change for those.


## Configuration

Once you have imported the config file, you will find it at `config/localization.php`.

**Important**: Before you start changing the values, you still need to set the "main language" of your page. If your main language is `fr`, please add this to your `config/app.php` file under `'fallback_locale' => 'fr',`.

We asume that your fallback language has always translated pages. We get the current locale via four ways: 

1. First we determine the local with the subdomain of the URL the user is coming from
  
If there is no subdomain added, we get the locale from:

2. an already set language cookie
3. or the browsers prefered language
4. or at the end we fall back to the `fallback_locale`

>*Note*: The value `locale` in `config/app.php` has no impact and is going to overwritten by ` tongue()->detect();`  in `app/Providers/RouteServiceProvider.php`


### Configuration values

- `domain` (default: `null`)

You don't need to worry about this, only when you are using domains with multiple dots, like: `155ad73e.eu.ngrok.io`. Without it, we cannot check what your subdomain is.

- `beautify_url` (default: `true`)

Makes the URL BEAUTIFUL 💁‍♀️. ( Use to set fallback language to mydomain.com and not to en.mydomain.com). That is why I even created this package. I just could not find this! 😭

- `subdomains` (default: `[]`)

Sometimes you would like to have your admin panel as a subdomain URL. Here you can whitelist those subdomains (only important if those URLs are using the [middleware](https://github.com/pmochine/Laravel-Tongue#middleware-)).

- `aliases` (default: `[]`)
Sometimes you would like to specify aliases to use custom subdomains instead of locale codes. For example: 
```
  gewinnen.domain.com --> "de"
  gagner.domain.com --> "fr",
```

- `acceptLanguage` (default: `true`)

Use this option to enable or disable the use of the browser 💻 settings during locale detection.

- `cookie_localization` (default: `true`)

Use this option to enable or disable the use of cookies 🍪 during the locale detection.

- `cookie_serialize` (default: `false`)

If you have not changed anything in your middleware "EncryptCookies", you don't need to change anything here as well. [More](https://laravel.com/docs/5.6/upgrade#upgrade-5.6.30)

- `prevent_redirect` (default: `false`)

Important for debugging, when you want to deactivate the middleware `speaks-tongue`.

- `supportedLocales` (default: `🇬🇧🇩🇪🇪🇸🇫🇷🇭🇺`)

Don't say anyone that I copied it from [mcamara](https://github.com/mcamara/laravel-localization) 🤫

## Route translation 

If you want to use translated routes (en.yourdomain.com/welcome, fr.yourdomain.com/bienvenue), proceed as follows:

First, create language files for the languages that you support:

`resources/lang/en/routes.php`:

```php
  return [
    
    // route name => route translation
    'welcome' => 'welcome',
    'user_profile' => 'user/{username}',
  
  ];
```

`resources/lang/fr/routes.php`:

```php
  return [
    
    // route name => route translation
    'welcome' => 'bienvenue',
    'user_profile' => 'utilisateur/{username}',
    
  ];
```

Then, here is how you define translated routes in `routes/web.php`:

```php
  Route::group([ 'middleware' => [ 'speaks-tongue' ]], function() {
    
      Route::get(dialect()->interpret('routes.welcome'), 'WelcomeController@index');
    
  });
```

You can, of course, name the language files as you wish, and pass the proper prefix (routes. in the example) to the interpret() method.

## Helper Functions - (finally something useful 😎)

This package provides useful helper functions that you can use - for example - in your views:

### Translate your current URL into the given language

```php
  <a href="{{ dialect()->current('fr') }}">See the french version</a>
```

### Get all translated URL except the current URL

```php
  @foreach (dialect()->translateAll(true) as $locale => $url)
      <a href="{{ $url }}">{{ $locale }}</a>
  @endforeach
```

You can pass `false` as parameter so it won't exclude the current URL. 

### Translate URL to the language you want

```php
  <a href="{{ dialect()->translate('routes.user_profile', [ 'username' => 'JohnDoe' ], 'fr') }}">See JohnDoe's profile</a>
  // Result: https://fr.example.com/utilisateur/JohnDoe 
```
 > Remember: Set the translation in the lang folder

Use `dialect()->translate($routeName, $routeAttributes = null, $locale = null)` to generate an alternate version of the given route. This will return an URL with the proper subdomain and also translate the URI if necessary.

You can pass route parameters if necessary. If you don't give a specific locale, it will use the current locale ☺️.

### Redirect URL to the language you want

```php
  <a href="{{ dialect()->redirectUrl(route('home'), 'fr') }}">See Homepage in French</a>
  // Result: https://fr.example.com 
```

Use `dialect()->redirectUrl($url = null, $locale = null);` to redirect for example to the same URL but in different locale. ***Warning***: Works only when the paths are not translated. Use `dialect()->translate()` for that.

### Get your config supported locale list
```php
  $collection = tongue()->speaking(); //returns collection
```
Remember it returns a collection. You can add methods to it ([see available methods](https://laravel.com/docs/5.6/collections#available-methods))
Examples: 
```php
  $keys = tongue()->speaking()->keys()->all(); //['en','de',..]
  $sorted = tongue()->speaking()->sort()->all(); //['de','en',..]
```

Additionally, you can even get some addtional information:

```php
  tongue()->speaking('BCP47', 'en'); // en-GB
  tongue()->speaking('subdomains'); // ['admin']
  tongue()->speaking('subdomains', 'admin'); // true
  tongue()->speaking('aliases'); // ['gewinnen' => 'de', 'gagner' => 'fr]
  tongue()->speaking('aliases', 'gewinnen'); //' de'
```


### Get the current language that is set
```php
  $locale = tongue()->current(); //de
```
Or if you like you can get the full name, the alphabet script, the native name of the language & the regional code.
```php
  $name = tongue()->current('name'); //German
  $script = tongue()->current('script'); //Latn
  $native = tongue()->current('native'); //Deutsch
  $regional = tongue()->current('regional'); //de_DE
```

 ## How to Switch Up the Language 🇬🇧->🇩🇪
 For example with a selector:
 
```php
  <ul>
      @foreach(tongue()->speaking()->all() as $localeCode => $properties)
          <li>
              <a rel="alternate" hreflang="{{ $localeCode }}" href="{{ dialect()->current($localeCode) }}">
                  {{ $properties['native'] }}
              </a>
          </li>
      @endforeach
  </ul>
```
Or in a controller far far away...
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
## Upgrade Guide 🎢
### Upgrade to 2.x.x from 1.x.x
There are little changes that might be important for you.

- We added two new config elements in localization. `domain` and `aliases`. Add these like [here](https://github.com/pmochine/Laravel-Tongue/blob/master/config/localization.php).
- Add `APP_DOMAIN` in your .env if you have a complicated domain, like: `155ad73e.eu.ngrok.io`
- Now you are able to use aliases in your subdomain. For example: `gewinnen.domain.com --> "de"`
- If a subdomain is invalid, it returns to the latest valid locale subdomain.

### Support for Laravel 7.22.0 up to Laravel 8.41.0

If you want to use:
>PHP >=7.3 and at least 7.22.0 <= Laravel <=8.41.0

you need to download the version 3.0.0.

```bash
  composer require pmochine/laravel-tongue:3.0.0
```

### Support for Laravel 6.x.x up to Laravel 7.21.0

If you want to use:
>PHP >=7.2 and at least 6.x.x <= Laravel <=7.21.0

you need to download the version 2.2.1 or lower.

```bash
  composer require pmochine/laravel-tongue:2.2.1
```

### Support for Laravel 5.x.x

If you want to use:
>PHP >=7.0 and at least 5.4 <= Laravel <=5.8

you need to download the version 2.0.0 or lower.

```bash
  composer require pmochine/laravel-tongue:2.0.0
```
 
## Security

If you discover any security related issues, please don't email me. I'm afraid 😱. avidofood@protonmail.com

## Credits

Now comes the best part! 😍
This package is based on

 - https://github.com/hoyvoy/laravel-subdomain-localization
 - https://github.com/mcamara/laravel-localization

Oh come on. You read everything?? If you liked it so far, hit the ⭐️ button to give me a 🤩 face. 
