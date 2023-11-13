<?php

namespace Volistx\FrameworkKernel\Helpers;

class SHA256Hasher
{
    /**
     * Get information about a hashed value.
     *
     * @param string $hashedValue The hashed value
     *
     * @return array The information about the hashed value
     */
    public static function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Create a SHA256 hash of a value.
     *
     * @param string $value   The value to be hashed
     * @param array  $options Additional options (e.g., salt)
     *
     * @return string|false The hashed value or false on failure
     */
    public static function make(string $value, array $options = []): string|false
    {
        $salt = $options['salt'] ?? '';
        return hash('sha256', $value . $salt);
    }

    /**
     * Check if a value matches a SHA256 hashed value.
     *
     * @param string $value       The value to be checked
     * @param mixed  $hashedValue The hashed value to compare against
     * @param array  $options     Additional options (e.g., salt)
     *
     * @return bool True if the value matches the hashed value, false otherwise
     */
    public static function check(string $value, mixed $hashedValue, array $options = []): bool
    {
        $salt = $options['salt'] ?? '';

        if (strlen($hashedValue) === 0) {
            return false;
        }

        return $hashedValue === hash('sha256', $value . $salt);
    }
}