<?php

namespace Volistx\FrameworkKernel\ValidationRules;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class RequestsCountValidationRule extends ValidationRuleBase
{
    private IUserLoggingService $loggingService;

    public function __construct(array $inputs)
    {
        parent::__construct($inputs);
        $this->loggingService = Container::getInstance()->make(IUserLoggingService::class);
    }

    public function Validate(): bool|array
    {
        $sub_id = $this->inputs['token']->subscription()->first()->id;
        $plan = $this->inputs['plan'];
        $planRequestsLimit = $plan['data']['requests'] ?? null;

        $requestsMadeCount = $this->loggingService->GetSubscriptionLogsCount($sub_id, Carbon::now());
        if (!$requestsMadeCount) {
            return [
                'message' => Messages::E500(),
                'code'    => 500,
            ];
        }

        if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
            return [
                'message' => Messages::E429(),
                'code'    => 429,
            ];
        }

        return true;
    }
}
