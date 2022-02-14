<?php

namespace Volistx\FrameworkKernel\Classes;

class SHA256Hasher
{
    public static function info($hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * @return false|string
     */
    public static function make(string $value, array $options = []): string|false
    {
        $salt = $options['salt'] ?? '';

        $hash = hash('sha256', $value.$salt);

        return $hash;
    }

    public static function check(string $value, $hashedValue, array $options = []): bool
    {
        $salt = $options['salt'] ?? '';

        if (strlen($hashedValue) === 0) {
            return false;
        }

        return $hashedValue === hash('sha256', $value.$salt);
    }
}
