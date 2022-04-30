<?php

namespace Volistx\FrameworkKernel\Helpers;

class SHA256Hasher
{
    public static function info($hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    public static function make(string $value, array $options = []): string|false
    {
        $salt = $options['salt'] ?? '';

        return hash('sha256', $value.$salt);
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
