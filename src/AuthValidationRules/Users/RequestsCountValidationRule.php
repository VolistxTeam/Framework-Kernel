<?php

namespace Volistx\FrameworkKernel\AuthValidationRules\Users;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Plans;
use Volistx\FrameworkKernel\Facades\Subscriptions;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class RequestsCountValidationRule extends ValidationRuleBase
{
    private IUserLoggingService $loggingService;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->loggingService = Container::getInstance()->make(IUserLoggingService::class);
    }

    public function Validate(): bool|array
    {
        $sub_id = Subscriptions::getSubscription()->id;
        $plan = Plans::getPlan();

        if (isset($plan['data']['requests'])) {
            $requestsMadeCount = $this->loggingService->GetSubscriptionLogsCountInPlanDuration($sub_id);
            $planRequestsLimit = $plan['data']['requests'] ?? null;

            if ($requestsMadeCount === null) {
                return [
                    'message' => Messages::E500(trans('volistx::request_count.can_not_retrieve')),
                    'code'    => 500,
                ];
            }

            if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
                return [
                    'message' => Messages::E403(trans('volistx::request_count.exceeded_limit')),
                    'code'    => 429,
                ];
            }
        }

        return true;
    }
}
