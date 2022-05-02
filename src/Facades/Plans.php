<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class Plans extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Plans';
    }
}
