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

    /**
     * Create a new RequestsCountValidationRule instance.
     *
     * @param Request $request The HTTP request object.
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->loggingService = Container::getInstance()->make(IUserLoggingService::class);
    }

    /**
     * Validates the number of requests made by the user.
     *
     * @return bool|array Returns true if the request count is within the limit, otherwise returns an array with error message and code.
     */
    public function Validate(): bool|array
    {
        $subscription = Subscriptions::getSubscription();
        $plan = Plans::getPlan();

        // If the plan has a requests limit
        if (isset($plan['data']['requests'])) {
            $requestsMadeCount = $this->loggingService->GetSubscriptionLogsCountInPlanDuration($subscription->user_id, $subscription->id);
            $planRequestsLimit = $plan['data']['requests'] ?? null;

            // If the requests count cannot be retrieved, deny access
            if ($requestsMadeCount === null) {
                return [
                    'message' => Messages::E500(trans('volistx::request_count.can_not_retrieve')),
                    'code'    => 500,
                ];
            }

            // If the plan requests limit is not set or the requests count exceeds the limit, deny access
            if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
                return [
                    'message' => Messages::E403(trans('volistx::request_count.exceeded_limit')),
                    'code'    => 429,
                ];
            }
        }

        // Allow access if none of the above conditions are met
        return true;
    }
}