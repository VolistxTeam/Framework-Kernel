<?php

namespace Volistx\FrameworkKernel\Helpers;

class PlansCenter
{
    private mixed $plan = null;

    /**
     * Set the plan.
     *
     * @param mixed $plan The plan
     *
     * @return void
     */
    public function setPlan(mixed $plan): void
    {
        $this->plan = $plan;
    }

    /**
     * Get the plan.
     *
     * @return mixed The plan
     */
    public function getPlan(): mixed
    {
        return $this->plan;
    }
}
