<?php

namespace Volistx\FrameworkKernel\Enums;

enum SubscriptionStatus: int
{
    case ACTIVE = 0;
    case INACTIVE = 1;
    case DEACTIVATED = 2;
    case EXPIRED = 3;
    case CANCELLED = 4;
}
