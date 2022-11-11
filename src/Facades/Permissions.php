<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static check($getToken, string $module, string $string)
 */
class Permissions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Permissions';
    }
}
