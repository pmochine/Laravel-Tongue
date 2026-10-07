<?php

namespace Pmochine\LaravelTongue\Tests\Feature;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Dialect;
use Pmochine\LaravelTongue\Tests\Fixtures\Article;
use Pmochine\LaravelTongue\Tests\TestCase;

/**
 * dialect()->localizedRoutes() registers translated routes for every locale (#28).
 * So route:cache and Octane work, and the locale is detected per request.
 */
class LocalizedRoutesTest extends TestCase
{
    protected function defineRoutes($router)
    {
        app('translator')->getLoader()->addNamespace('Tongue', dirname(__DIR__).'/lang');

        $router->bind('article', function ($value) {
            return (new Article)->resolveRouteBinding($value) ?? abort(404);
        });

        $middleware = [AddQueuedCookiesToResponse::class, SubstituteBindings::class, 'detects-tongue', 'speaks-tongue'];

        $router->middleware($middleware)->group(function () {
            dialect()->localizedRoutes(function () {
                Route::get(dialect()->interpret('Tongue::routes.hello_user'), function ($username) {
                    return app()->getLocale().':'.$username;
                })->name('hello');

                Route::prefix('admin')->group(function () {
                    Route::get(dialect()->interpret('Tongue::routes.good_evening'), function () {
                        return app()->getLocale();
                    })->name('admin.evening');
                });

                // The same path in every locale, with a translated slug
                Route::get('news/{article}', function (Article $article) {
                    return app()->getLocale();
                })->name('news');

                // A translated path with a translated slug
                Route::get(dialect()->interpret('Tongue::routes.article_slug'), function (Article $article) {
                    return app()->getLocale().':'.$article->getRouteKey();
                })->name('article');

                // GET and POST have the same path in English, but other paths in German
                Route::get(dialect()->interpret('Tongue::routes.form'), function () {
                    return 'form';
                })->name('form');

                Route::post(dialect()->interpret('Tongue::routes.submit'), function () {
                    return 'submitted:'.request('message');
                })->name('submit');

                // A translated prefix
                Route::prefix(dialect()->interpret('Tongue::routes.shop'))->group(function () {
                    Route::get(dialect()->interpret('Tongue::routes.good_night'), function () {
                        return 'shop';
                    })->name('shop.night');
                });
            });
        });
    }

    #[Test]
    public function it_registers_one_route_per_locale_with_its_own_path()
    {
        // The fallback locale keeps the name. Spanish, French and Hungarian have no translation, so they share it.
        $this->assertSame('hello/{username}', Route::getRoutes()->getByName('hello')->uri());
        $this->assertSame('hallo/{username}', Route::getRoutes()->getByName('hello.de')->uri());
        $this->assertNull(Route::getRoutes()->getByName('hello.fr'));

        $this->assertSame('admin/guten-abend', Route::getRoutes()->getByName('admin.evening.de')->uri());

        // A path that is the same in every locale is one route
        $this->assertSame('news/{article}', Route::getRoutes()->getByName('news')->uri());
        $this->assertNull(Route::getRoutes()->getByName('news.de'));
    }

    #[Test]
    public function each_locale_reaches_its_path()
    {
        $this->assertRoutesWork();
    }

    #[Test]
    public function the_routes_work_from_the_route_cache()
    {
        $this->cacheRoutes();

        $this->assertRoutesWork();
    }

    #[Test]
    public function the_path_of_another_locale_redirects_to_the_path_of_the_locale()
    {
        $this->call('GET', $this->getUri('hello/john?tab=2', 'de'))->assertRedirect($this->getUri('hallo/john?tab=2', 'de'));
        $this->call('GET', $this->getUri('hallo/john'))->assertRedirect($this->getUri('hello/john'));
    }

    #[Test]
    public function the_redirect_keeps_an_alias_and_ignores_a_whitelisted_subdomain()
    {
        app('config')->set('localization.aliases', ['gewinnen' => 'de']);
        app('config')->set('localization.subdomains', ['admin']);

        $this->call('GET', $this->getUri('hello/john', 'gewinnen'))->assertRedirect($this->getUri('hallo/john', 'gewinnen'));

        // Like twister(): Tongue does not redirect a whitelisted subdomain
        $this->call('GET', $this->getUri('hello/john', 'admin'), [], ['tongue-locale' => 'de'])->assertOk();
    }

    #[Test]
    public function the_first_supported_locale_keeps_the_name_if_the_fallback_locale_is_not_supported()
    {
        app('config')->set('app.fallback_locale', 'it');

        dialect()->localizedRoutes(function () {
            Route::get(dialect()->interpret('Tongue::routes.good_night'), function () {
                return 'night';
            })->name('night');
        });

        // German comes first in the supported locales of the test config
        $this->assertSame('gute-nacht', Route::getRoutes()->getByName('night')->uri());
        $this->assertSame('good-night', Route::getRoutes()->getByName('night.en')->uri());
    }

    #[Test]
    public function get_and_post_with_the_same_path_keep_their_own_translation()
    {
        $this->call('GET', $this->getUri('kontakt', 'de'))->assertOk()->assertSee('form');
        $this->call('POST', $this->getUri('absenden', 'de'), ['message' => 'hallo'])->assertOk()->assertSee('submitted:hallo');
        $this->call('POST', $this->getUri('contact'), ['message' => 'hello'])->assertOk()->assertSee('submitted:hello');

        $this->assertEquals($this->getUri('absenden', 'de'), app('dialect')->translate('submit', [], 'de'));
        $this->assertEquals($this->getUri('kontakt', 'de'), app('dialect')->translate('form', [], 'de'));
    }

    #[Test]
    public function a_redirect_keeps_the_method_of_a_post_request()
    {
        $this->call('POST', $this->getUri('contact', 'de'), ['message' => 'hallo'])
            ->assertStatus(307)
            ->assertRedirect($this->getUri('absenden', 'de'));
    }

    #[Test]
    public function a_translated_prefix_belongs_to_its_route()
    {
        $this->call('GET', $this->getUri('laden/gute-nacht', 'de'))->assertOk()->assertSee('shop');

        $this->assertEquals($this->getUri('laden/gute-nacht', 'de'), app('dialect')->translate('shop.night', [], 'de'));

        $this->call('GET', $this->getUri('shop/good-night', 'de'))->assertRedirect($this->getUri('laden/gute-nacht', 'de'));
        $this->call('GET', $this->getUri('laden/gute-nacht', 'de'))->assertOk()->assertSee('shop');
    }

    #[Test]
    public function a_translated_slug_works_on_the_first_request()
    {
        // The locale is detected before route model binding
        $this->call('GET', $this->getUri('news/wichtige-aenderung', 'de'))->assertOk()->assertSee('de');
        $this->call('GET', $this->getUri('news/important-change', 'fr'))->assertOk()->assertSee('fr');
        $this->call('GET', $this->getUri('artikel/wichtige-aenderung', 'de'))->assertOk()->assertSee('de:wichtige-aenderung');
    }

    #[Test]
    public function the_path_and_the_slug_of_another_locale_redirect_to_the_locale()
    {
        $this->call('GET', $this->getUri('article/important-change', 'de'))->assertRedirect($this->getUri('artikel/wichtige-aenderung', 'de'));
    }

    #[Test]
    public function the_slug_of_a_shared_path_redirects_from_each_of_its_locales()
    {
        // English and Spanish share the path article/{article}, but each has its own slug
        $this->call('GET', $this->getUri('article/cambio-importante', 'de'))->assertRedirect($this->getUri('artikel/wichtige-aenderung', 'de'));
    }

    #[Test]
    public function a_translation_key_belongs_to_its_route_in_every_locale()
    {
        dialect()->localizedRoutes(function () {
            Route::prefix('keys')->group(function () {
                // The paths are interpreted in another order than the routes are registered
                $submit = dialect()->interpret('Tongue::routes.submit');
                $form = dialect()->interpret('Tongue::routes.form');

                Route::get($form, function () {
                    return 'form';
                })->name('keys.form');

                Route::post($submit, function () {
                    return 'submitted';
                })->name('keys.submit');
            });
        });

        $this->assertSame('Tongue::routes.form', Route::getRoutes()->getByName('keys.form')->getAction('tongue')['key']);
        $this->assertSame('Tongue::routes.submit', Route::getRoutes()->getByName('keys.submit')->getAction('tongue')['key']);
    }

    #[Test]
    public function swapped_paths_in_two_calls_are_rejected()
    {
        dialect()->localizedRoutes(function () {
            Route::get(dialect()->interpret('Tongue::routes.swap_a'), function () {
                return 'a';
            })->name('swap.a');
        });

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('have the same path');

        dialect()->localizedRoutes(function () {
            Route::get(dialect()->interpret('Tongue::routes.swap_b'), function () {
                return 'b';
            })->name('swap.b');
        });
    }

    #[Test]
    public function swapped_paths_with_overlapping_methods_are_rejected()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('have the same path');

        dialect()->localizedRoutes(function () {
            Route::match(['GET', 'POST'], dialect()->interpret('Tongue::routes.swap_a'), function () {
                return 'a';
            })->name('swap.a');

            Route::get(dialect()->interpret('Tongue::routes.swap_b'), function () {
                return 'b';
            })->name('swap.b');
        });
    }

    #[Test]
    public function two_routes_with_swapped_paths_are_rejected()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('have the same path');

        dialect()->localizedRoutes(function () {
            Route::get(dialect()->interpret('Tongue::routes.swap_a'), function () {
                return 'a';
            })->name('swap.a');

            Route::get(dialect()->interpret('Tongue::routes.swap_b'), function () {
                return 'b';
            })->name('swap.b');
        });
    }

    #[Test]
    public function a_taken_route_name_with_the_locale_suffix_is_rejected()
    {
        Route::get('taken', function () {
            return 'taken';
        })->name('night.de');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('[night.de]');

        dialect()->localizedRoutes(function () {
            Route::get(dialect()->interpret('Tongue::routes.good_night'), function () {
                return 'night';
            })->name('night');
        });
    }

    #[Test]
    public function the_urls_use_the_route_of_the_locale()
    {
        $this->assertUrlsWork();
    }

    #[Test]
    public function the_urls_use_the_route_of_the_locale_from_the_route_cache()
    {
        $this->cacheRoutes();

        $this->assertUrlsWork();
    }

    protected function assertRoutesWork(): void
    {
        $this->call('GET', $this->getUri('hallo/john', 'de'))->assertOk()->assertSee('de:john');
        $this->call('GET', $this->getUri('hello/john', 'fr'))->assertOk()->assertSee('fr:john');
        $this->call('GET', $this->getUri('hello/john'))->assertOk()->assertSee('en:john');
        $this->call('GET', $this->getUri('admin/guten-abend', 'de'))->assertOk()->assertSee('de');
        $this->call('GET', $this->getUri('news/wichtige-aenderung', 'de'))->assertOk()->assertSee('de');
    }

    protected function assertUrlsWork(): void
    {
        $this->call('GET', $this->getUri('hallo/john', 'de'))->assertOk();

        $this->assertEquals($this->getUri('hello/john', 'en'), app('dialect')->current('en'));
        $this->assertEquals($this->getUri('hello/john', 'fr'), app('dialect')->translateAll()['fr']);
        $this->assertEquals($this->getUri('hello/john'), app('dialect')->alternates()['en']);

        $this->assertEquals($this->getUri('hello/jane', 'en'), app('dialect')->translate('hello', ['username' => 'jane'], 'en'));
        $this->assertEquals($this->getUri('hallo/jane', 'de'), app('dialect')->translate('hello', ['username' => 'jane']));
        $this->assertEquals($this->getUri('hallo/jane', 'de'), app('dialect')->translate('Tongue::routes.hello_user', ['username' => 'jane']));
        $this->assertEquals($this->getUri('admin/good-evening', 'en'), app('dialect')->translate('admin.evening.de', [], 'en'));

        $this->assertEquals($this->getUri('hello/john', 'en'), app('dialect')->translateUrl($this->getUri('hallo/john', 'de'), 'en'));

        $this->call('GET', $this->getUri('news/wichtige-aenderung', 'de'))->assertOk();

        $this->assertEquals($this->getUri('news/important-change', 'en'), app('dialect')->current('en'));
    }

    /**
     * Like php artisan route:cache: the app loads the compiled routes and never runs the route file.
     */
    protected function cacheRoutes(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $route->prepareForSerialization();
        }

        app('router')->setCompiledRoutes(app('router')->getRoutes()->compile());

        $this->assertInstanceOf(CompiledRouteCollection::class, app('router')->getRoutes());

        // A new process: Dialect knows nothing from interpret()
        $this->app->forgetInstance(Dialect::class);
        Facade::clearResolvedInstance('dialect');
    }
}
