<?php

/*
Please DO NOT touch any routes here!!
*/

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Volistx\FrameworkKernel\Http\Controllers\AdminLogController;
use Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController;
use Volistx\FrameworkKernel\Http\Controllers\PlanController;
use Volistx\FrameworkKernel\Http\Controllers\SubscriptionController;
use Volistx\FrameworkKernel\Http\Controllers\UserController;
use Volistx\FrameworkKernel\Http\Controllers\UserLogController;
use Volistx\FrameworkKernel\Http\Middleware\AdminAuthMiddleware;
use Volistx\FrameworkKernel\Http\Middleware\JsonBodyValidationFilteringMiddleware;

Route::prefix('sys-bin')->group(function () {
    Route::middleware(['throttle:100,1'])->group(function () {
        Route::get('ping', function () {
            return response()->json([
                'status' => 'ok',
                'time' => Carbon::now()->toDateTimeString(),
            ]);
        });

        Route::get('timestamp', function () {
            return response(Carbon::now()->timestamp);
        });
    });

    Route::prefix('admin')->middleware(AdminAuthMiddleware::class)->group(function () {
        Route::prefix('users')->group(function () {
            Route::middleware(JsonBodyValidationFilteringMiddleware::class)->group(function () {
                Route::patch('/{userId}', [UserController::class, 'UpdateUser']);
            });

            Route::post('/', [UserController::class, 'CreateUser']);
            Route::delete('/{userId}', [UserController::class, 'DeleteUser']);
            Route::get('/{userId}', [UserController::class, 'GetUser']);

            Route::prefix('/{userId}/')->group(function () {
                Route::prefix('subscriptions')->group(function () {
                    Route::middleware(JsonBodyValidationFilteringMiddleware::class)->group(function () {
                        Route::post('/', [SubscriptionController::class, 'CreateSubscription']);
                        Route::post('/{subscriptionId}', [SubscriptionController::class, 'MutateSubscription']);
                        Route::patch('/{subscriptionId}/cancel', [SubscriptionController::class, 'CancelSubscription']);
                    });

                    Route::patch('/{subscriptionId}/revert-cancel', [SubscriptionController::class, 'RevertCancelSubscription']);
                    Route::delete('/{subscriptionId}', [SubscriptionController::class, 'DeleteSubscription']);
                    Route::get('/', [SubscriptionController::class, 'GetSubscriptions']);
                    Route::get('/{subscriptionId}', [SubscriptionController::class, 'GetSubscription']);
                    Route::get('/{subscriptionId}/logs', [SubscriptionController::class, 'GetSubscriptionLogs']);
                    Route::get('/{subscriptionId}/usages', [SubscriptionController::class, 'GetSubscriptionUsages']);
                });

                Route::prefix('personal-tokens')->group(function () {
                    Route::middleware(JsonBodyValidationFilteringMiddleware::class)->group(function () {
                        Route::post('/', [PersonalTokenController::class, 'CreatePersonalToken']);
                        Route::patch('/{tokenId}', [PersonalTokenController::class, 'UpdatePersonalToken']);
                    });

                    Route::delete('/{tokenId}', [PersonalTokenController::class, 'DeletePersonalToken']);
                    Route::post('/{tokenId}/reset', [PersonalTokenController::class, 'ResetPersonalToken']);
                    Route::get('/{tokenId}', [PersonalTokenController::class, 'GetPersonalToken']);
                    Route::get('/', [PersonalTokenController::class, 'GetPersonalTokens']);
                    Route::post('/sync', [PersonalTokenController::class, 'Sync']);
                });
            });
        });

        Route::prefix('plans')->group(function () {
            Route::middleware(JsonBodyValidationFilteringMiddleware::class)->group(function () {
                Route::post('/', [PlanController::class, 'CreatePlan']);
                Route::patch('/{planId}', [PlanController::class, 'UpdatePlan']);
            });

            Route::delete('/{planId}', [PlanController::class, 'DeletePlan']);
            Route::get('/', [PlanController::class, 'GetPlans']);
            Route::get('/{planId}', [PlanController::class, 'GetPlan']);
        });

        Route::prefix('logs')->group(function () {
            Route::get('/', [AdminLogController::class, 'GetAdminLogs']);
            Route::get('/{logId}', [AdminLogController::class, 'GetAdminLog']);
        });

        Route::prefix('user-logs')->group(function () {
            Route::get('/', [UserLogController::class, 'GetUserLogs']);
            Route::get('/{logId}', [UserLogController::class, 'GetUserLog']);
        });
    });
});
