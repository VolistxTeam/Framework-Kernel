<?php

namespace Volistx\FrameworkKernel\ValidationRules;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Repositories\Interfaces\IUserLogRepository;

class RequestsCountValidationRule extends ValidationRuleBase
{
    private IUserLogRepository $userLogRepository;

    public function __construct(array $inputs)
    {
        parent::__construct($inputs);
        $this->userLogRepository = Container::getInstance()->make(IUserLogRepository::class);
    }

    public function Validate(): bool|array
    {
        $sub_id = $this->inputs['token']->subscription()->first()->id;
        $plan = $this->inputs['plan'];
        $planRequestsLimit = $plan['data']['requests'] ?? null;

        $requestsMadeCount = $this->userLogRepository->FindSubscriptionLogsCount($sub_id, Carbon::now());

        if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
            return [
                'message' => Messages::E429(),
                'code'    => 429,
            ];
        }

        return true;
    }
}
