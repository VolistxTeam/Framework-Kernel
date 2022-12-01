<?php

namespace Volistx\FrameworkKernel\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Volistx\FrameworkKernel\Facades\AccessTokens;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\Permissions;
use Volistx\FrameworkKernel\Services\Interfaces\IUserLoggingService;

class UserLogController extends Controller
{
    private IUserLoggingService $userLoggingService;

    public function __construct(IUserLoggingService $userLoggingService)
    {
        $this->module = 'logs';
        $this->userLoggingService = $userLoggingService;
    }

    public function GetUserLog(Request $request, $log_id): JsonResponse
    {
        try {
            if (!Permissions::check(AccessTokens::getToken(), $this->module, 'view')) {
                return response()->json(Messages::E401(), 401);
            }

            $validator = Validator::make([
                'log_id' => $log_id,
            ], [
                'log_id' => ['bail', 'required', 'uuid', 'exists:user_logs,id'],
            ], [
                'log_id.required' => trans('volistx::log_id.required'),
                'log_id.uuid'     => trans('volistx::log_id.uuid'),
                'log_id.exists'   => trans('volistx::log_id.exists'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $log = $this->userLoggingService->GetLog($log_id);

            if (!$log) {
                return response()->json(Messages::E404(), 404);
            }

            return response()->json($log);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }

    public function GetUserLogs(Request $request): JsonResponse
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
                'page.integer'  => trans('volistx::page.integer'),
                'limit.integer' => trans('volistx::limit.integer'),
            ]);

            if ($validator->fails()) {
                return response()->json(Messages::E400($validator->errors()->first()), 400);
            }

            $logs = $this->userLoggingService->GetLogs($search, $page, $limit);

            if (!$logs) {
                return response()->json(Messages::E400(trans('volistx::invalid_search_column')), 400);
            }

            return response()->json($logs);
        } catch (Exception $ex) {
            return response()->json(Messages::E500(), 500);
        }
    }
}
