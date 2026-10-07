# Changelog

All notable changes to this package are in this file. The package follows [Semantic Versioning](https://semver.org).

## [6.0.0] - Unreleased

Version 6 supports Laravel 11, 12 and 13. Read the [upgrade guide](README.md#upgrade-to-6xx-from-5xx) before you update.

### Breaking changes

- The package needs PHP 8.2 or higher and Laravel 11, 12 or 13. Laravel 13 needs PHP 8.3 or higher.
- Support for Laravel 8, 9 and 10 and for PHP 7.4 and 8.1 is removed. Use version 5.0.0 for these versions. Version 5 gets no more updates.
- `bakame/laravel-domain-parser` must be version 1.3 or higher.
- `dialect()->translate()` and `dialect()->current()` encode route attributes in translated paths. A space becomes `%20`, and `#`, `?` and `%` are encoded too.
- The home page URL has no trailing slash: `https://fr.example.com` instead of `https://fr.example.com/`.
- The constructors of `Tongue`, `Dialect` and `Localization\Locale` take no arguments. They read the application with `app()` for each call.

### Added

- Support for Laravel 11, 12 and 13 (#56).
- The service provider registers the middleware alias `speaks-tongue`. If the app defines this alias, the service provider keeps it.
- New option `alias_urls` (default `false`). If it is `true`, the URLs that Tongue builds use the alias of a locale as subdomain. The middleware then redirects to the alias (#52, #47).
- `dialect()->translate()` also accepts the name of a route, not only a translation key (#53). For a route without translation, Laravel builds the path like `route()`. Attributes that are not in the path become the query string.
- `dialect()->translate()` uses the binding field of a route, like `{post:slug}`, for a model.
- Middleware `TongueDetectsLocale` with the alias `detects-tongue`. It detects the locale for each request, for example with Laravel Octane (#54).
- Contract `LocalizedUrlRoutable` for translated slugs. `current()`, `translateAll()` and `translate()` use the route key of a model in the target locale.
- `dialect()->translateUrl($url, $locale)` translates a URL of the app, for example the previous page. `tongue()->back()` uses it, so a language switch in a controller keeps the translated path (#40).
- `dialect()->alternates()` gives the URLs of the current page in all locales and `x-default`, for `hreflang` links. A locale can set its own hreflang with the key `hreflang` in `supportedLocales`.

### Fixed

- `dialect()->current()` threw a `TypeError` on routes from `Route::view()` (#55).
- `dialect()->current()` put a model from route model binding as JSON into the URL. It now uses the value from the URL.
- `dialect()->current()` kept the path of the current locale for a named route with a translated path.
- The helpers `tongue()` and `dialect()` built a new instance for each call. Routes from `dialect()->interpret()` were lost for the facade and the container. Now helpers, facades and container share one instance.
- `Dialect` cached the URL of the first request. With Octane or several requests in one test, links got the path of an old request.
- If an app resolved `Tongue` during boot, `Tongue` changed the locale of the Octane worker application. `Tongue` now uses the application of the current request (#54).
- The Accept-Language fallback read `$_SERVER`, which does not belong to the current request in Octane (#54).
- `dialect()->translate()` with route attributes threw "Undefined array key" on a URL without a path.
- A pattern for missing optional route parameters removed the path between two of them.
- Without attributes, `translate()` left missing optional route parameters in the URL as `{page?}`.
- A missing optional parameter after a dot, like in `files/{name}.{extension?}`, left the dot in a translated URL.
- Translated routes in a route group with a prefix, like `Route::prefix('admin')`, lost the prefix in the translated URL. Each route keeps its own prefix. For a translation key, `translate()` uses the prefix of the first route with this key.
- A translation with a leading slash, like `'/welcome'`, did not match its route.
- An alias that is also a locale code caused a redirect loop.
- Aliases are case-insensitive, like hosts. An alias like `Gewinnen` in the configuration did not match `gewinnen.domain.com`.
- A subdomain that is equal to the first label of the domain, like `example.example.com`, was not found.
- For an app in a subfolder, like `https://example.com/shop`, translated URLs lost `/shop`.
- Implicitly nullable parameters for PHP 8.4.

### Changed

- The tests use Orchestra Testbench and PHPUnit attributes instead of the Browser Kit testing package.
- The tests run with PHPUnit 11.5, 12.5 or 13. The CI uses PHPUnit 13 on PHP 8.4 and 8.5 with Laravel 12 and 13.
- GitHub Actions replaces Travis CI. StyleCI, SensioLabs Insight and Coveralls configuration files are removed.

## Older versions

For 5.0.0 and older versions, read the [GitHub releases](https://github.com/pmochine/Laravel-Tongue/releases).
