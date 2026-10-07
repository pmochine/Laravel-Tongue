<?php

namespace Pmochine\LaravelTongue\Middleware;

use Closure;

class TongueDetectsLocale
{
    /**
     * Handle an incoming request.
     * Detects the locale for each request. Use it with Laravel Octane,
     * where a service provider only runs once per worker.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        tongue()->detect();

        return $next($request);
    }
}
