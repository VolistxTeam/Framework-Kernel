<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static E401()
 * @method static E400(string $first)
 * @method static E500(string $string)
 * @method static E403(string $string)
 */
class Messages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Messages';
    }
}
