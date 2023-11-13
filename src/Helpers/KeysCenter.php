<?php

namespace Volistx\FrameworkKernel\Helpers;

use RandomLib\Factory;
use SecurityLib\Strength;

class KeysCenter
{
    /**
     * Generates a random key.
     *
     * @param int $length The length of the key (default: 64)
     *
     * @return string The generated key
     */
    public static function randomKey(int $length = 64): string
    {
        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::HIGH));
        return $generator->generateString($length, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Generates a random salted key.
     *
     * @param int $keyLength  The length of the key (default: 64)
     * @param int $saltLength The length of the salt (default: 16)
     *
     * @return array The generated key and salt
     */
    public static function randomSaltedKey(int $keyLength = 64, int $saltLength = 16): array
    {
        return [
            'key' => self::randomKey($keyLength),
            'salt' => self::randomKey($saltLength),
        ];
    }
}