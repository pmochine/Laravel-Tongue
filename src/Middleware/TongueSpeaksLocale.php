<?php

namespace Pmochine\LaravelTongue\Middleware;

use Closure;
use Pmochine\LaravelTongue\Misc\Config;

class TongueSpeaksLocale
{
    /**
     * Handle an incoming request.
     * Redirect if tongue does not speak the locale
     * language :P.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (tongue()->twister() && ! Config::preventRedirect()) {
            return dialect()->redirect(dialect()->redirectURL());
        }

        // A route from localizedRoutes() with the path of another locale, like de.example.com/hello
        if (! Config::preventRedirect() && $url = dialect()->localizedRouteRedirectUrl()) {
            return dialect()->redirect($url);
        }

        return $next($request);
    }
}
