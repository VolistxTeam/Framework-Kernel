<?php

namespace VolistxTeam\VSkeletonKernel\ValidationRules;

use Carbon\Carbon;
use VolistxTeam\VSkeletonKernel\Facades\Messages;
use VolistxTeam\VSkeletonKernel\Repositories\Interfaces\IUserLogRepository;

class RequestsCountValidationRule extends ValidationRuleBase
{
    private IUserLogRepository $userLogRepository;

    public function __construct(array $inputs, IUserLogRepository $userLogRepository)
    {
        parent::__construct($inputs);
        $this->userLogRepository = $userLogRepository;
    }

    public function Validate(): bool|array
    {
        $sub_id = $this->inputs['token']->subscription()->first()->id;
        $plan = $this->inputs['plan'];
        $planRequestsLimit = $plan['data']['requests'] ?? null;

        $requestsMadeCount = $this->userLogRepository->FindLogsBySubscriptionCount($sub_id, Carbon::now());

        if (!$planRequestsLimit || ($planRequestsLimit != -1 && $requestsMadeCount >= $planRequestsLimit)) {
            return [
                'message' => Messages::E429(),
                'code'    => 429,
            ];
        }

        return true;
    }
}
