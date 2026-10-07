<?php

use Pmochine\LaravelTongue\Tests\Fixtures\Article;

// An article with a translated slug in each locale
app('router')->bind('article', function ($value) {
    return (new Article)->resolveRouteBinding($value) ?? abort(404);
});

app('router')->group(['middleware' => ['Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse', 'Illuminate\Routing\Middleware\SubstituteBindings']], function () {
    app('router')->get('not-localized', function () {
        return response('not-localized');
    });

    app('router')->get('/', function () {
        return response('home');
    })->name('home');

    // A language switch in a controller, like in the README
    app('router')->post('switch/{locale}', function ($locale) {
        return tongue()->speaks($locale)->back();
    });

    // Route::view() adds the parameters "view", "data", "status" and "headers" (#55)
    app('router')->view('privacy', 'privacy')->name('privacy');

    app('router')->group(['middleware' => ['Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale']], function () {
        app('router')->get('localized', function () {
            return response('localized');
        });

        app('router')->get(dialect()->interpret('Tongue::routes.good_morning'), function () {
            return response('translated route without parameter');
        });

        app('router')->get(dialect()->interpret('Tongue::routes.hello_user'), function () {
            return response('translated route with parameter');
        });

        app('router')->get(dialect()->interpret('Tongue::routes.good_night'), function () {
            return response('named translated route');
        })->name('good_night');

        app('router')->get(dialect()->interpret('Tongue::routes.with_slash'), function () {
            return response('translated route with a leading slash');
        })->name('with_slash');

        app('router')->prefix('admin')->group(function () {
            app('router')->get(dialect()->interpret('Tongue::routes.good_evening'), function () {
                return response('translated route in a prefix group');
            });

            // The same translation key as above, now with a prefix and a leading slash in the translation
            app('router')->get(dialect()->interpret('Tongue::routes.with_slash'), function () {
                return response('translated route with a leading slash in a prefix group');
            })->name('admin.with_slash');
        });

        app('router')->get('blog/{page?}', function () {
            return response('blog');
        })->name('blog');

        app('router')->get('posts/{post:slug}', function () {
            return response('post');
        })->name('post');

        // The route name is also the translation key
        app('router')->get('articles/{post:slug}', function () {
            return response('article');
        })->name('Tongue::routes.article');

        app('router')->get('files/{base}.{extension?}', function () {
            return response('file');
        })->name('file');

        // In English both routes have the path "contact"
        app('router')->get(dialect()->interpret('Tongue::routes.form'), function () {
            return response('form');
        })->name('Tongue::routes.form');

        app('router')->post(dialect()->interpret('Tongue::routes.submit'), function () {
            return response('submitted');
        })->name('Tongue::routes.submit');

        app('router')->get(dialect()->interpret('Tongue::routes.article_slug'), function () {
            return response('translated route with a translated slug');
        });

        app('router')->get('news/{article}', function () {
            return response('route with a translated slug');
        })->name('news');
    });
});
