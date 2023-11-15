<?php

namespace Volistx\FrameworkKernel\Services\Interfaces;

interface IUserLoggingService
{
    /**
     * Create a new user log entry.
     *
     * @param array $inputs
     *
     * @return void
     */
    public function CreateUserLog(array $inputs): void;

    /**
     * Get a user log entry by log ID.
     *
     * @param string $logId
     *
     * @return mixed
     */
    public function GetLog(string $logId): mixed;

    /**
     * Get all user log entries with pagination support.
     *
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return array|null
     */
    public function GetLogs(string $search, int $page, int $limit): array|null;

    /**
     * Get all subscription log entries for a user and subscription with pagination support.
     *
     * @param string $userId
     * @param string $subscriptionId
     * @param string $search
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     */
    public function GetSubscriptionLogs(string $userId, string $subscriptionId, string $search, int $page, int $limit): array;

    /**
     * Get the count of subscription log entries for a user and subscription within the plan duration.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return int
     */
    public function GetSubscriptionLogsCountInPlanDuration(string $userId, string $subscriptionId): int;

    /**
     * Get all user log entries for a specific subscription.
     *
     * @param string $userId
     * @param string $subscriptionId
     *
     * @return array
     */
    public function GetSubscriptionUsages(string $userId, string $subscriptionId): array;
}
