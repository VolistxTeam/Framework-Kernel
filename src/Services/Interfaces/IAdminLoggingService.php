<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

interface IAdminLoggingService
{
    /**
     * Create a new admin log entry.
     *
     * @param array $inputs
     *
     * @return void
     */
    public function CreateAdminLog(array $inputs): void;

    /**
     * Get an admin log entry by log ID.
     *
     * @param string $logId
     *
     * @return mixed
     */
    public function GetAdminLog(string $logId): mixed;

    /**
     * Get all admin log entries with pagination support.
     *
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return array|null
     */
    public function GetAdminLogs(string $search, int $page, int $limit): array|null;
}
