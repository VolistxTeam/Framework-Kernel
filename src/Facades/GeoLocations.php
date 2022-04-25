<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class GeoLocations extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'geoLocation';
    }
}
