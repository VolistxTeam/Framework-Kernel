<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Permissions service.
 *
 * @method static bool  check(mixed $key, string $module, string $operation) Checks if a key has the required permissions for a module and operation.
 * @method static array getAdminPermissions()                                Get the admin permissions.
 * @method static array getServicesPermissions()                             Get the services permissions.
 */
class Permissions extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Permissions';
    }
}
