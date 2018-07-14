<?php

app('router')->group(['middleware' => 'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse'], function () {
    app('router')->get('not-localized', function () {
        return response('not-localized');
    });

    app('router')->group(['middleware' => ['Hoyvoy\Localization\Middleware\Localization']], function () {
        app('router')->get('localized', function () {
            return response('localized');
        });

        app('router')->get(app('localization.router')->resolve('Localize::routes.good_morning'), function () {
            return response('translated route without parameter');
        });

        app('router')->get(app('localization.router')->resolve('Localize::routes.hello_user'), function () {
            return response('translated route with parameter');
        });
    });
});
