<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Services\Interfaces\IAdminLoggingService;

class AdminLogController extends Controller
{
    private IAdminLoggingService $adminLoggingService;

    public function __construct(IAdminLoggingService $adminLoggingService)
    {
        $this->module = 'logs';
        $this->adminLoggingService = $adminLoggingService;
    }

    public function GetAdminLog(Request $request, $log_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'log_id' => $log_id,
            ], [
                'log_id' => ['bail', 'required', 'uuid', 'exists:admin_logs,id'],
            ], [
                'log_id.required' => 'The log ID is required.',
                'log_id.uuid'     => 'The log ID must be a valid uuid.',
                'log_id.exists'   => 'The log with the given ID was not found.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $log = $this->adminLoggingService->GetAdminLog($log_id);

            if (!$log) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json($log);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetAdminLogs(Request $request): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view-all')) {
                return response()->json(Messages::E401(), 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $validator = Validator::make([
                'page'  => $page,
                'limit' => $limit,
            ], [
                'page'  => ['bail', 'sometimes', 'integer'],
                'limit' => ['bail', 'sometimes', 'integer'],
            ], [
                'page.integer'  => 'The page must be an integer.',
                'limit.integer' => 'The limit must be an integer.',
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->adminLoggingService->GetAdminLogs($search, $page, $limit);

            if (!$logs) {
                return response()->json(Messages::E400('Invalid search column'), 400);
            }

            return response()->json($logs);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
