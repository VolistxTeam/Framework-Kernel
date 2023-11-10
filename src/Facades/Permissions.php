<?php
namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing the Permissions service.
 *
 * @method static check($getToken, string $module, string $operation) Check if the user has permission for a specific module and string.
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