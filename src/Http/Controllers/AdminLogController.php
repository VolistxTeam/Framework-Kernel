<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class AdminLogController extends Controller
{
    private IAdminLoggingService $adminLoggingService;

    /**
     * AdminLogController constructor.
     *
     * @param IAdminLoggingService $adminLoggingService The admin logging service
     */
    public function __construct(IAdminLoggingService $adminLoggingService)
    {
        $this->module = 'admin-logs';
        $this->adminLoggingService = $adminLoggingService;
    }

    /**
     * Get an admin log.
     *
     * @param Request $request The HTTP request
     * @param string $logId The log ID
     *
     * @return JsonResponse The JSON response
     */
    public function GetAdminLog(Request $request, string $logId): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = $this->getModuleValidation($this->module)->generateGetValidation([
                'log_id' => $logId,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $log = $this->adminLoggingService->GetAdminLog($logId);

            if (!$log) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json($log);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    /**
     * Get admin logs.
     *
     * @param Request $request The HTTP request
     *
     * @return JsonResponse The JSON response
     */
    public function GetAdminLogs(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = $this->getModuleValidation($this->module)->generateGetAllValidation([
                'page' => $page,
                'limit' => $limit,
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->adminLoggingService->GetAdminLogs($search, $page, $limit);

            if (!$logs) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            return response()->json($logs);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}