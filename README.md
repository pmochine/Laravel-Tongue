


# Laravel Tongue 👅 - Multilingual subdomain URLs and redirects


[![tests](https://github.com/pmochine/Laravel-Tongue/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/pmochine/Laravel-Tongue/actions/workflows/tests.yml)

[![Packagist](https://img.shields.io/packagist/v/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://poser.pugx.org/pmochine/laravel-tongue/d/total.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://img.shields.io/packagist/l/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)

![Laravel Tongue](img/laravel-tongue.png)

**If you are looking for an easy package for subdomain multilingual URLs, this package is for you.  😜**

 **Old Way**: `https://example.com/de`, `https://example.com/fr` etc. <br>
 **New Way**: `https://de.example.com`, `https://fr.example.com` etc.

## Requirements

| Laravel Tongue | Laravel | PHP |
| --- | --- | --- |
| 6.x | 11, 12, 13 | 8.2 or higher (Laravel 13 needs 8.3 or higher) |
| 5.x | 8.41 to 10 | 7.4, 8.1 or higher |

For older Laravel versions, read [Older Laravel versions](#older-laravel-versions).
If you upgrade from 5.x, read the [upgrade guide](#upgrade-to-6xx-from-5xx).

## Installation in 4 Steps

### 1: Add with composer 💻
```bash
  composer require pmochine/laravel-tongue
```

### 2: Publish Configuration File (you need to change some things to use it 😎)

```bash
  php artisan vendor:publish --provider="Pmochine\LaravelTongue\ServiceProvider" --tag="config"
```
### 3: The Middleware 🌐
**Laravel Tongue** comes with a middleware that can be used to enforce the use of a language subdomain. For example the user calls example.com it goes directly to fr.example.com. 

The package registers the middleware alias `speaks-tongue` for you. You do not need to add it yourself.

If your app defines the alias itself, the package keeps your definition. Since Laravel 11 you do this in `bootstrap/app.php`:

```php
  use Illuminate\Foundation\Configuration\Middleware;

  ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias([
          'speaks-tongue' => \Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale::class,
      ]);
  })
```

An app that still has `app/Http/Kernel.php` can add the same line to the `$middlewareAliases` array.

### 4: Add in your Env 🔑
```shell
  APP_DOMAIN=yourdomain.com #Only important for domains with many dots like: '155ad73e.eu.ngrok.io'
  SESSION_DOMAIN=.yourdomain.com #Read down below why
```
  **Important!** Note the dot before the domain name. Now the session is available in every subdomain 🙃. This is important because you want to save all your cookie 🍪 data in one place and not in many other.

Laravel finds the service provider of the package with package auto-discovery. You do not need to register it.

## Usage - (or to make it runnable 🏃‍♂️)


### Locale detection 🔍

Open `app/Providers/AppServiceProvider.php` and add this to the `boot()` method:

```php
  public function boot(): void
  {
      // This will guess a locale from the current HTTP request
      // and set the application locale.
      tongue()->detect();
      
      //If you use Carbon you can set the Locale right here.
      \Carbon\Carbon::setLocale(tongue()->current()); 
  }
```

Laravel loads your routes after the `boot()` method of the `AppServiceProvider`. So `tongue()->detect()` sets the locale before `dialect()->interpret()` translates your routes.

If your app still has `app/Providers/RouteServiceProvider.php`, you can call `tongue()->detect()` at the start of its `boot()` method instead.

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

For more information about Middleware, please refer to <a href="https://laravel.com/docs/middleware">Laravel docs</a>.

### Laravel Octane

Octane boots your app one time for many requests. A service provider does not see each request. So do not call `tongue()->detect()` in a service provider. Call it in a middleware that runs before all other middleware:

```php
  // app/Http/Middleware/DetectTongue.php
  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class DetectTongue
  {
      public function handle(Request $request, Closure $next): Response
      {
          tongue()->detect();

          return $next($request);
      }
  }

  // bootstrap/app.php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->prepend(\App\Http\Middleware\DetectTongue::class);
  })
```

Octane registers your routes one time, before the first request. So translated routes with `dialect()->interpret()` do not work with Octane. All other features work.

### Route caching

`php artisan route:cache` works for routes without translated paths. For translated routes, the cache keeps the paths of one locale only. If you use `dialect()->interpret()`, do not cache your routes.

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

>*Note*: The value `locale` in `config/app.php` has no impact. `tongue()->detect()` overwrites it.


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

- `alias_urls` (default: `false`)

By default, the aliases only work for incoming requests. The URLs that Tongue builds still use the locale, like `de.domain.com`. If you set `alias_urls` to `true`, these URLs use the first alias of the locale, like `gewinnen.domain.com`. The middleware then redirects `de.domain.com` and the other aliases of the locale to `gewinnen.domain.com`.

The beautiful URL of the fallback locale (`beautify_url`) wins over its alias. Tongue ignores an alias that is also a locale code or a whitelisted subdomain.

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

First, create language files for the languages that you support. Since Laravel 9 the language files are in the `lang` folder of your app. If you do not have this folder, run `php artisan lang:publish`.

`lang/en/routes.php`:

```php
  return [
    
    // route name => route translation
    'welcome' => 'welcome',
    'user_profile' => 'user/{username}',
  
  ];
```

`lang/fr/routes.php`:

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

`$routeName` can be a translation key, like `routes.user_profile`, or the name of a route, like `home`:

```php
  Route::get('/', HomeController::class)->name('home');

  dialect()->translate('home', [], 'fr'); // https://fr.example.com
```

If the route got its path from `dialect()->interpret()`, you get the translated path of the locale.

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
Remember it returns a collection. You can add methods to it ([see available methods](https://laravel.com/docs/collections#available-methods))
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
      'locale' => ['required', Rule::in(tongue()->speaking()->keys()->all())],
    ])['locale'];

    return tongue()->speaks($locale)->back();
  } 
```

`back()` keeps the path of the previous page and only changes the subdomain. It cannot translate the path, because the controller does not know the route of the previous page. For translated routes, use links with `dialect()->current($locale)` like in the selector above.
## Upgrade Guide 🎢
### Upgrade to 6.x.x from 5.x.x

Version 6 supports Laravel 11, 12 and 13. It needs PHP 8.2 or higher.

1. Make sure that your app runs on Laravel 11 or higher and PHP 8.2 or higher.
2. Update the package:

   ```bash
     composer require pmochine/laravel-tongue:^6.0
   ```

3. You can delete your own `speaks-tongue` alias from `app/Http/Kernel.php` or `bootstrap/app.php`. The package registers it now. If you keep your alias, the package uses it.
4. If your app has no `app/Providers/RouteServiceProvider.php` anymore, move `tongue()->detect()` to the `boot()` method of `app/Providers/AppServiceProvider.php`.
5. Optional: add the new key `'alias_urls' => false,` to your published `config/localization.php`. If the key is missing, the package uses `false`.

These changes can affect your app:

- `dialect()->translate()` and `dialect()->current()` now also find a route by its name. Before, a route name that was no translation key kept the path of the current page.
- `tongue()`, `dialect()`, the facades and `app('tongue')` now return the same instance. Before, each call of a helper built a new instance.
- `dialect()->translate()` and `dialect()->current()` encode route attributes for the path, like `route()` of Laravel. A space becomes `%20`.
- The home page URL has no trailing slash: `https://fr.example.com` instead of `https://fr.example.com/`.
- If you extend `Tongue` or `Dialect`: the constructors take no arguments, and `Dialect` has no `$app` property.

### Upgrade to 2.x.x from 1.x.x
There are little changes that might be important for you.

- We added two new config elements in localization. `domain` and `aliases`. Add these like [here](https://github.com/pmochine/Laravel-Tongue/blob/master/config/localization.php).
- Add `APP_DOMAIN` in your .env if you have a complicated domain, like: `155ad73e.eu.ngrok.io`
- Now you are able to use aliases in your subdomain. For example: `gewinnen.domain.com --> "de"`
- If a subdomain is invalid, it returns to the latest valid locale subdomain.

## Older Laravel versions

### Support for Laravel 8.41 up to Laravel 10

If you want to use:
>PHP ^7.4 or ^8.1 and Laravel 8.41 up to Laravel 10

you need to download the version 5.0.0. This version gets no more updates.

```bash
  composer require pmochine/laravel-tongue:^5.0
```

### Support for Laravel 9.x.x

If you want to use:
>PHP <=8.0 and Laravel 9.x.x

you need to download the version 4.0.0.

```bash
  composer require pmochine/laravel-tongue:4.0.0
```

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
