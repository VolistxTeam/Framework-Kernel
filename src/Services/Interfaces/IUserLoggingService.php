<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

interface IUserLoggingService
{
    public function CreateUserLog(array $inputs);

    public function GetLog($log_id);

    public function GetLogs($search, $page, $limit);

    public function GetSubscriptionLogs($subscription_id, $search ,$page, $limit);

    public function GetSubscriptionLogsCount($subscription_id);

    public function GetSubscriptionUsages($subscription_id);
}
