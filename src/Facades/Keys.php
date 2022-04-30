<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class Keys extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Keys';
    }
}
