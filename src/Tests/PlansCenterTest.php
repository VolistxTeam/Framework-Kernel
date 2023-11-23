<?php

use PHPUnit\Framework\TestCase;
use Volistx\FrameworkKernel\Helpers\PlansCenter;

class PlansCenterTest extends TestCase
{
    private ?PlansCenter $plansCenter;

    protected function setUp(): void
    {
        $this->plansCenter = new PlansCenter();
    }

    protected function tearDown(): void
    {
        $this->plansCenter = null;
    }

    public function testSetPlan()
    {
        $plan = 'my_plan';
        $this->plansCenter->setPlan($plan);

        $this->assertEquals($plan, $this->plansCenter->getPlan());
    }

    public function testGetPlan()
    {
        $plan = 'my_plan';
        $this->plansCenter->setPlan($plan);

        $this->assertEquals($plan, $this->plansCenter->getPlan());
    }
}