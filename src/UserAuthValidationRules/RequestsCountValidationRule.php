<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

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
            $requestsMadeCount = $this->loggingService->GetSubscriptionLogsCount($sub_id);
            $planRequestsLimit = $plan['data']['requests'] ?? null;

            if ($requestsMadeCount === null) {
                return [
                    'message' => Messages::E500('The request count could not be retrieved.'),
                    'code'    => 500,
                ];
            }

            if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
                return [
                    'message' => Messages::E403('You have reached the limit of requests for this plan. Please upgrade your plan if you want to continue using this service.'),
                    'code'    => 429,
                ];
            }
        }

        return true;
    }
}
