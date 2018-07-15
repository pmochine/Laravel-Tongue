<?php

app('router')->group(['middleware' => 'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse'], function () {

    app('router')->get('not-localized', function () {
        return response('not-localized');
    });

    app('router')->group(['middleware' => ['Pmochine\LaravelTongue\Middleware\TongueSpeaksLocale']], function () {
        app('router')->get('localized', function () {
            return response('localized');
        });

        app('router')->get(app('dialect')->interpret('Tongue::routes.good_morning'), function () {
            return response('translated route without parameter');
        });

        app('router')->get(app('dialect')->interpret('Tongue::routes.hello_user'), function () {
            return response('translated route with parameter');
        });
    });
});
