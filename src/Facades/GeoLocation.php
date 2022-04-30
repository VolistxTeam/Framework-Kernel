<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class GeoLocation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'GeoLocation';
    }
}
