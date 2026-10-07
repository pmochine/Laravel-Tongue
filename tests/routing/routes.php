<?php

app('router')->group(['middleware' => ['Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse', 'Illuminate\Routing\Middleware\SubstituteBindings']], function () {
    app('router')->get('not-localized', function () {
        return response('not-localized');
    });

    app('router')->get('/', function () {
        return response('home');
    })->name('home');

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
        });

        app('router')->get('blog/{page?}', function () {
            return response('blog');
        })->name('blog');

        app('router')->get('posts/{post:slug}', function () {
            return response('post');
        })->name('post');
    });
});
