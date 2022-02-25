<?php

namespace Volistx\FrameworkKernel\Services;

use Volistx\FrameworkKernel\DataTransferObjects\AdminLogDTO;
use Volistx\FrameworkKernel\Repositories\AdminLogRepository;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class LocalAdminLoggingService implements IAdminLoggingService
{
    private AdminLogRepository $logRepository;

    public function __construct(AdminLogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function CreateAdminLog(array $inputs)
    {
        $this->logRepository->Create($inputs);
    }

    public function GetAdminLog($log_id)
    {
        $log = $this->logRepository->Find($log_id);

        return $log ?? AdminLogDTO::fromModel($log)->GetDTO();
    }

    public function GetAdminLogs(string $search, int $page, int $limit)
    {
        $logs = $this->logRepository->FindAll($search, $page, $limit);

        $logDTOs = [];
        foreach ($logs->items() as $log) {
            $logDTOs[] = AdminLogDTO::fromModel($log)->GetDTO();
        }

        return [
            'pagination' => [
                'per_page' => $logs->perPage(),
                'current'  => $logs->currentPage(),
                'total'    => $logs->lastPage(),
            ],
            'items' => $logDTOs,
        ];
    }
}
