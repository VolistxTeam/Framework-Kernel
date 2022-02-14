<?php

namespace Volistx\FrameworkKernel\Repositories\Interfaces;

interface IUserLogRepository
{
    public function Create(array $inputs);

    public function FindById($log_id);

    public function FindAll($needle, $page, $limit);

    public function FindLogsBySubscription($subscription_id, $needle, $page, $limit);

    public function FindLogsBySubscriptionCount($subscription_id, $date);
}
