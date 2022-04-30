<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class HMAC extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'HMAC';
    }
}
