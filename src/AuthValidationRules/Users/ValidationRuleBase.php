<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Http\Request;

/**
 * Base class for validation rules.
 */
abstract class ValidationRuleBase
{
    protected Request $request;

    /**
     * Create a new ValidationRuleBase instance.
     *
     * @param Request $request The HTTP request object.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Validate the request.
     *
     * @return bool|array Returns true if the validation passes, otherwise returns an array with error message and code.
     */
    abstract public function Validate(): bool|array;
}
