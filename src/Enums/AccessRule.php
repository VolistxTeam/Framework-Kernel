<?php

namespace Volistx\FrameworkKernel\Enums;

enum AccessRule: int
{
    case NONE = 0;
    case BLACKLIST = 1;
    case WHITELIST = 2;
}