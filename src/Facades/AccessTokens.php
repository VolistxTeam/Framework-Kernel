<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class AccessTokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'AccessTokens';
    }
}
