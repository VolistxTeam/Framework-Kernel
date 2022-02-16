<?php

namespace Volistx\FrameworkKernel\Repositories\Interfaces;

interface IUserLogRepository
{
    public function Create(array $inputs);

    public function FindById($log_id);

    public function FindAll($needle, $page, $limit);

    public function FindSubscriptionLogs($subscription_id, $needle, $page, $limit);

    public function FindSubscriptionLogsCount($subscription_id, $date);

    public function FindSubscriptionLogsInMonth($subscription_id, $date);
}
