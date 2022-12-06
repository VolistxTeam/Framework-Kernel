<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

interface IUserLoggingService
{
    public function CreateUserLog(array $inputs);

    public function GetLog($log_id);

    public function GetLogs($search, $page, $limit);

    public function GetSubscriptionLogs($user_id, $subscription_id, $search, $page, $limit);

    public function GetSubscriptionLogsCountInPlanDuration($user_id, $subscription_id);

    public function GetSubscriptionUsages($user_id, $subscription_id);
}
