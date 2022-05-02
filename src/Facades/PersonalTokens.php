<?php

namespace Volistx\FrameworkKernel\Facades;

use Illuminate\Support\Facades\Facade;

class PersonalTokens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'PersonalTokens';
    }
}
