<?php

namespace Volistx\FrameworkKernel\Classes;

class PermissionsCenter
{
    public array $admin_permissions = [
        'subscriptions:create',
        'subscriptions:update',
        'subscriptions:delete',
        'subscriptions:view',
        'subscriptions:view-all',
        'subscriptions:logs',
        'subscriptions:stats',

        'personal-tokens:create',
        'personal-tokens:update',
        'personal-tokens:delete',
        'personal-tokens:reset',
        'personal-tokens:view',
        'personal-tokens:view-all',
        'personal-tokens:logs',

        'plans:create',
        'plans:update',
        'plans:delete',
        'plans:view',
        'plans:view-all',
        'plans:logs',

        'logs:view',
        'logs:view-all',
    ];

    public array $services_permissions;

    public function __construct()
    {
        $this->services_permissions = config('volistx.services_permissions');
    }

    public function check($key, $module, $operation): bool
    {
        return in_array("$module:$operation", $key->permissions) || in_array("$module:*", $key->permissions) || in_array('*', $key->permissions);
    }

    public function getAdminPermissions(): array
    {
        return $this->admin_permissions;
    }

    public function getServicesPermissions(): array
    {
        return $this->services_permissions;
    }
}
