<?php

namespace Volistx\FrameworkKernel\RequestPreProcessors;

abstract class RequestPreProcessorBase
{
    protected array $inputs;

    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }

    abstract public function Process(): bool|array;
}
