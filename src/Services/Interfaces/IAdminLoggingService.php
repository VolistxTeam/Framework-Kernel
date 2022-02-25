<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

interface IAdminLoggingService
{
    public function CreateAdminLog(array $inputs);

    public function GetAdminLog($log_id);

    public function GetAdminLogs(string $search, int $page, int $limit);
}
