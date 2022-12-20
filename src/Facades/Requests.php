<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class Requests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'Requests';
    }
}
