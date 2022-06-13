<?php

namespace Volistx\FrameworkKernel\UserAuthValidationRules;

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

        if (isset($plan['data']['requests'])) {
            $requestsMadeCount = $this->loggingService->GetSubscriptionLogsCount($sub_id, Carbon::now());
            $planRequestsLimit = $plan['data']['requests'] ?? null;

            if ($requestsMadeCount === null) {
                return [
                    'message' => Messages::E500('The request count could not be retrieved.'),
                    'code'    => 500,
                ];
            }

            if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
                return [
                    'message' => Messages::E429('You have reached the limit of requests for this plan.'),
                    'code'    => 429,
                ];
            }
        }

        return true;
    }
}
