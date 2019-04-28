<?php

namespace Pmochine\LaravelTongue\Misc;

use Illuminate\Contracts\Encryption\DecryptException;

class Cookie
{
    protected $key;

    public function __construct(string $cookieKey)
    {
        $this->key = $cookieKey;
    }

    public function save(string $content): void
    {
        cookie()->queue(cookie()->forever($this->key, $content));
    }

    public function get(): ?string
    {
        if (!$this->has()) return null;

        try {
            //Somehow I got this error: unserialize(): Error at offset 0 of 2 bytes
            //I needed to change decrypt(value, unserialize = false);
            return app('encrypter')->decrypt(request()->cookie($this->key), false);
        } catch (DecryptException $e) {
            //Somehow the middleware for decrypting does not kick in here...
            //but it even fails if we use php artisan <something> (weird)
            //if it happes we can simply give it normally back
            return request()->cookie($this->key);
        } catch (\Exception $e) {
            //So I don't return a cookie in that case
            return null;
        }
    }

    public function has(): bool
    {
        return request()->hasCookie($this->key);
    }
}
