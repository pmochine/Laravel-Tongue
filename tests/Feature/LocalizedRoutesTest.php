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
