<?php

namespace Volistx\FrameworkKernel\Helpers;

class PlansCenter
{
    private $plan;

    public function setPlan($plan)
    {
        $this->plan = $plan;
    }

    public function getPlan()
    {
        return $this->plan;
    }
}
