<?php

namespace Volistx\FrameworkKernel\Helpers;

use Illuminate\Support\Str;

class KeysCenter
{
    public static function randomKey(int $length = 64): string
    {
        return Str::random($length);
    }

    public static function randomSaltedKey(int $keyLength = 64, int $saltLength = 16): array
    {
        return [
            'key'  => self::randomKey($keyLength),
            'salt' => self::randomKey($saltLength),
        ];
    }
}
