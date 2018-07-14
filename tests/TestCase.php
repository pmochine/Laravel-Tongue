<?php

namespace Pmochine\LaravelTongue\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Orchestra\Testbench\BrowserKit\TestCase as OrchestraTestCase;


class TestCase extends OrchestraTestCase
{
    protected $scheme = 'https';
    protected $domain = 'laraveltongue.dev';

    protected $defaultLocale = 'en';

    public function setUp()
    {
        parent::setUp();

        $this->refreshConfig();
    }

    /**
     * Refresh the configuration.
     */
    public function refreshConfig()
    {
        // app('config')->set('localization.domain', $this->domain);
        app('config')->set('app.fallback_locale', $this->defaultLocale);
    }

    /**
     * Refresh application & config during a test.
     */
    public function refresh()
    {
        $this->refreshApplication();
        $this->refreshConfig();
    }

    /**
     * Visit the given URI and return the Response.
     *
     * @param string $method
     * @param string $path
     * @param string $locale
     * @param array  $parameters
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     *
     * @return Response
     */
    protected function sendRequest($method, $path, $locale = null, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->getUri($path, $locale);

        $this->setRequestContext($method, $path, $locale, $parameters, $cookies, $files, $server, $content);

        return $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Set Request context for the package components.
     *
     * @param string $method
     * @param string $path
     * @param string $locale
     * @param array  $parameters
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     *
     * @return Response
     */
    protected function setRequestContext($method, $path, $locale = null, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $uri = $this->getUri($path, $locale);

        $request = Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);

        $this->app->instance('request', $request);

        app('tongue')->detect();

        $this->setRoutes();
    }

    /**
     * Return test Uri for the given locale and path.
     *
     * @param string $path
     * @param string $locale
     *
     * @return string
     */
    public function getUri($path, $locale = null)
    {
        $uri = $this->scheme.'://'.($locale ? $locale.'.' : '').$this->domain.'/'.$path;

        return $uri;
    }

    /**
     * Set routes for testing.
     *
     * @param bool|string $locale
     */
    protected function setRoutes($locale = false)
    {
        if ($locale) {
            app()->setLocale($locale);
        }

        // Load translated routes for testing
        // app('translator')->getLoader()->addNamespace('Localize', realpath(dirname(__FILE__)).'/lang');
        // app('translator')->load('Localize', 'routes', 'de');
        // app('translator')->load('Localize', 'routes', 'en');

        // Load routes for testing
       // app('files')->getRequire(__DIR__.'/routing/routes.php');
    }

    /**
     * Checks if the given response contains the given cookie(s).
     *
     * @param Response $response
     * @param array    $cookies
     *
     * @return bool
     */
    protected function responseHasCookies(Response $response, $cookies)
    {
        $responseCookies = $response->headers->getCookies();

        foreach ($cookies as $cookieName => $cookieValue) {
            $cookieFound = false;

            foreach ($responseCookies as $cookie) {

                // The cookie is found but with an unexpected value
                if ($cookieName == $cookie->getName()) {
                    $cookieFound = true;

                    if ($cookieValue != $cookie->getValue()) {
                        return false;
                    }
                }
            }

            if (!$cookieFound) {
                return false;
            }
        }

        return true;
    }
}
