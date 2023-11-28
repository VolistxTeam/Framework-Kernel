<?php

namespace Volistx\FrameworkKernel\Helpers;

class PermissionsCenter
{
    public array $adminPermissions = [
        '*', // Wildcard permission for all
        'subscriptions:*',
        'subscriptions:create',
        'subscriptions:mutate',
        'subscriptions:delete',
        'subscriptions:view',
        'subscriptions:view-all',
        'subscriptions:logs',
        'subscriptions:stats',
        'subscriptions:cancel',
        'subscriptions:uncancel',
        'personal-tokens:*',
        'personal-tokens:create',
        'personal-tokens:update',
        'personal-tokens:delete',
        'personal-tokens:reset',
        'personal-tokens:view',
        'personal-tokens:view-all',
        'personal-tokens:logs',
        'plans:*',
        'plans:create',
        'plans:update',
        'plans:delete',
        'plans:view',
        'plans:view-all',
        'plans:logs',
        'user-logs:*',
        'user-logs:view',
        'user-logs:view-all',
        'admin-logs:*',
        'admin-logs:view',
        'admin-logs:view-all',
    ];

    /**
     * Checks if a key has the required permissions for a module and operation.
     *
     * @param mixed  $key       The key object
     * @param string $module    The module name
     * @param string $operation The operation name
     *
     * @return bool True if the key has the required permissions, false otherwise
     */
    public function check(mixed $key, string $module, string $operation): bool
    {
        return in_array("$module:$operation", $key->permissions)
            || in_array("$module:*", $key->permissions)
            || in_array('*', $key->permissions);
    }

    /**
     * Get the admin permissions.
     *
     * @return array The admin permissions
     */
    public function getAdminPermissions(): array
    {
        return $this->adminPermissions;
    }

    /**
     * Get the services permissions.
     *
     * @return array The services permissions
     */
    public function getServicesPermissions(): array
    {
        return config('volistx.services_permissions');
    }
}
