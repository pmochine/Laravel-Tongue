<?php

namespace Pmochine\LaravelTongue\Middleware;

use Closure;

class TongueSpeaksLocale
{
    /**
     * Handle an incoming request.
     * Redirect if tongue does not speak the locale
     * language :P.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (tongue()->twister()) {
            return dialect()->redirect(dialect()->redirectURL());
        }

        return $next($request);
    }
}
