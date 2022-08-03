<?php

namespace Volistx\FrameworkKernel\Enums;

enum RateLimitMode: int
{
    case SUBSCRIPTION = 0;
    case IP = 1;
}
