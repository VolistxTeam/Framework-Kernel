<?php

namespace Volistx\FrameworkKernel\Helpers;

use RandomLib\Factory;
use SecurityLib\Strength;

class KeysCenter
{
    public static function randomKey(int $length = 64): string
    {
        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::HIGH));

        return $generator->generateString($length, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    public static function randomSaltedKey(int $keyLength = 64, int $saltLength = 16): array
    {
        return [
            'key'  => self::randomKey($keyLength),
            'salt' => self::randomKey($saltLength),
        ];
    }
}
