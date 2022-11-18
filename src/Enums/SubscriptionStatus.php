<?php

namespace Volistx\FrameworkKernel\Enums;

enum SubscriptionStatus: int
{
    case ACTIVE = 0;
    case INACTIVE = 1;
    case EXPIRED = 2;
    case CANCELLED = 3;
}
