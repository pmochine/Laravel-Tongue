# Laravel Tongue - Use your subdomain for multilingual urls and redirects


[![Build Status](https://travis-ci.org/pmochine/laravel-tongue.svg?branch=master)](https://travis-ci.org/pmochine/laravel-tongue)
[![styleci](https://styleci.io/repos/CHANGEME/shield)](https://styleci.io/repos/CHANGEME)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pmochine/laravel-tongue/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/CHANGEME/mini.png)](https://insight.sensiolabs.com/projects/CHANGEME)
[![Coverage Status](https://coveralls.io/repos/github/pmochine/laravel-tongue/badge.svg?branch=master)](https://coveralls.io/github/pmochine/laravel-tongue?branch=master)

[![Packagist](https://img.shields.io/packagist/v/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://poser.pugx.org/pmochine/laravel-tongue/d/total.svg)](https://packagist.org/packages/pmochine/laravel-tongue)
[![Packagist](https://img.shields.io/packagist/l/pmochine/laravel-tongue.svg)](https://packagist.org/packages/pmochine/laravel-tongue)

![Laravel Tongue](img/laravel-tongue.png)

## Installation

Install via composer
```bash
composer require pmochine/laravel-tongue
```

### Register Service Provider

**Note! This and next step are optional if you use laravel>=5.5 with package
auto discovery feature.**

Add service provider to `config/app.php` in `providers` section
```php
Pmochine\LaravelTongue\ServiceProvider::class,
```

### Register Facade

Register package facade in `config/app.php` in `aliases` section
```php
Pmochine\LaravelTongue\Facades\LaravelTongue::class,
```

### Publish Configuration File

```bash
php artisan vendor:publish --provider="Pmochine\LaravelTongue\ServiceProvider" --tag="config"
```

## Usage

CHANGE ME

## Security

If you discover any security related issues, please email 
instead of using the issue tracker.

## Credits

- [](https://github.com/pmochine/laravel-tongue)
- [All contributors](https://github.com/pmochine/laravel-tongue/graphs/contributors)

This package is bootstrapped with the help of
[melihovv/laravel-package-generator](https://github.com/melihovv/laravel-package-generator).
