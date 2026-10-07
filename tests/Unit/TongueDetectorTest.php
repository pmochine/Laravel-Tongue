<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Localization\TongueDetector;
use Pmochine\LaravelTongue\Misc\Config;
use Pmochine\LaravelTongue\Tests\TestCase;

class TongueDetectorTest extends TestCase
{
    #[Test]
    public function it_negotiates_the_language_from_the_header()
    {
        $this->assertEquals('de', $this->detect('de-AT,de;q=0.9,en;q=0.8'));
        $this->assertEquals('fr', $this->detect('it,fr;q=0.5'));
        $this->assertEquals('en', $this->detect('it'));
    }

    #[Test]
    public function it_reads_the_header_of_the_given_request_and_not_the_server_variable()
    {
        // In Octane, $_SERVER does not belong to the current request.
        $before = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';

        try {
            $this->assertEquals('en', $this->detect('zz'));
        } finally {
            if ($before === null) {
                unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $before;
            }
        }
    }

    protected function detect(string $acceptLanguage): string
    {
        $request = Request::create('https://laraveltongue.dev', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => $acceptLanguage]);

        return (new TongueDetector('en', Config::supportedLocales(), $request))->negotiateLanguage();
    }
}
