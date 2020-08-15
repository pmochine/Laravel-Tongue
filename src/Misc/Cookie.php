<?php

namespace Pmochine\LaravelTongue\Misc;

use Illuminate\Contracts\Encryption\DecryptException;

class Cookie
{
    protected $key;

    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected $serialize;

    public function __construct(string $cookieKey, bool $serialize = false)
    {
        $this->key = $cookieKey;
        $this->serialize = $serialize;
    }

    public function save(string $content): void
    {
        cookie()->queue(cookie()->forever($this->key, $content));
    }

    public function get(): ?string
    {
        if (! $this->has()) {
            return null;
        }

        try {
            // If you read this. You could help me out. It's quite ugly and not sure how to do it better.
            // Normally, the middleware "EncryptCookies" would kick in. But since we are calling this
            // before the middleware even kicks in, we need to decrypt it manually like in:
            // https://github.com/laravel/framework/blob/7bb90039c5cb42a8f5f2dd489d9936d1ee2668d2/src/Illuminate/Cookie/Middleware/EncryptCookies.php
            // The thing is that Laravel is updating the algorithm to decrypt the cookie all the time. And this breaks my code all the time
            // Now I'm using a "simple" version that needs to be changed in the future again.
            // Another bug that I used to get was: unserialize(): Error at offset 0 of 2 bytes, since the update 5.6.30
            // https://laravel.com/docs/5.6/upgrade#upgrade-5.6.30
            // I needed to change decrypt(value, unserialize = false);
            $value = app('encrypter')->decrypt($this->getCookie(), $this->serialize);
            // This part is new since Laravel 7.22.0 (Improve cookie encryption)
            // Not really sure, but I don't use the security improvement at all. At the end why should I? It's just the locale
            $pos = strpos($value, '|');

            return $pos !== false ? substr($value, $pos + 1) : null;
        } catch (DecryptException $e) {
            // Somehow the middleware for decrypting does not kick in here...
            // but it even fails if we use php artisan <something> (weird)
            // if it happes we can simply give it normally back
            return $this->getCookie();
        } catch (\Exception $e) {
            // So I don't return a cookie in that case
            return null;
        }
    }

    protected function getCookie(): ?string
    {
        $result = request()->cookie($this->key);

        if (is_array($result)) {
            return null;
        }

        return $result;
    }

    public function has(): bool
    {
        return request()->hasCookie($this->key);
    }
}
