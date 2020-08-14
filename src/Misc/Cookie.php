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
        if (!$this->has()) {
            return null;
        }

        try {
            // Normally, the middleware "EncryptCookies" would kick in. But since we are calling this
            // before the middleware even kicks in, we need to decrypt it manually like in:
            // https://github.com/laravel/framework/blob/7bb90039c5cb42a8f5f2dd489d9936d1ee2668d2/src/Illuminate/Cookie/Middleware/EncryptCookies.php
            // However I got this error: unserialize(): Error at offset 0 of 2 bytes, since the update 5.6.30
            // https://laravel.com/docs/5.6/upgrade#upgrade-5.6.30
            // I needed to change decrypt(value, unserialize = false);
            $value = app('encrypter')->decrypt($this->getCookie(), $this->serialize);
            // This part is new since Laravel 7.22.0 (Improve cookie encryption)
            return strpos($value, sha1($this->key) . '|') !== 0 ? null : substr($value, 41);
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
