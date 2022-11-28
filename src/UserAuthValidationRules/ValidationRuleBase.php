<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

use Illuminate\Http\Request;

abstract class ValidationRuleBase
{
    protected Request $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    abstract public function Validate(): bool|array;
}
