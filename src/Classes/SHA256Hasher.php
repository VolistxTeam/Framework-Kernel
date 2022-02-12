<?php

namespace VolistxTeam\VSkeletonKernel\Classes;

class SHA256Hasher
{
    public static function info($hashedValue)
    {
        return password_get_info($hashedValue);
    }

    public static function make($value, array $options = [])
    {
        $salt = $options['salt'] ?? '';

        $hash = hash('sha256', $value . $salt);
        return $hash;
    }

    public static function check($value, $hashedValue, array $options = [])
    {
        $salt = $options['salt'] ?? '';

        if (strlen($hashedValue) === 0) {
            return false;
        }

        return $hashedValue === hash('sha256', $value . $salt);
    }
}