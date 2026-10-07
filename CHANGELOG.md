# Changelog

All notable changes to this package are in this file. The package follows [Semantic Versioning](https://semver.org).

## [6.0.0] - Unreleased

Version 6 supports Laravel 11, 12 and 13. Read the [upgrade guide](README.md#upgrade-to-6xx-from-5xx) before you update.

### Breaking changes

- The package needs PHP 8.2 or higher and Laravel 11, 12 or 13. Laravel 13 needs PHP 8.3 or higher.
- Support for Laravel 8, 9 and 10 and for PHP 7.4 and 8.1 is removed. Use version 5.0.0 for these versions. Version 5 gets no more updates.
- `bakame/laravel-domain-parser` must be version 1.3 or higher.

### Added

- Support for Laravel 11, 12 and 13 (#56).
- The service provider registers the middleware alias `speaks-tongue`. If the app defines this alias, the service provider keeps it.
- New option `alias_urls` (default `false`). If it is `true`, the URLs that Tongue builds use the alias of a locale as subdomain. The middleware then redirects to the alias (#52, #47).
- `dialect()->translate()` also accepts the name of a route, not only a translation key (#53).

### Fixed

- `dialect()->current()` threw a `TypeError` on routes from `Route::view()` (#55).
- `dialect()->current()` put a model from route model binding as JSON into the URL. It now uses the value from the URL.
- `dialect()->current()` kept the path of the current locale for a named route with a translated path.
- The helpers `tongue()` and `dialect()` built a new instance for each call. Routes from `dialect()->interpret()` were lost for the facade and the container. Now helpers, facades and container share one instance.
- `Dialect` cached the URL of the first request. With Octane or several requests in one test, links got the path of an old request.
- `Tongue` is a scoped binding, so Octane builds it again for each request (#54).
- The Accept-Language fallback read `$_SERVER`, which does not belong to the current request in Octane (#54).
- `dialect()->translate()` with route attributes threw "Undefined array key" on a URL without a path.
- A pattern for missing optional route parameters removed the path between two of them.
- Implicitly nullable parameters for PHP 8.4.

### Changed

- The tests use Orchestra Testbench and PHPUnit attributes instead of the Browser Kit testing package.
- GitHub Actions replaces Travis CI. StyleCI, SensioLabs Insight and Coveralls configuration files are removed.

## Older versions

For 5.0.0 and older versions, read the [GitHub releases](https://github.com/pmochine/Laravel-Tongue/releases).
