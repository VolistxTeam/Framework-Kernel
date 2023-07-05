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

Route::prefix('sys-bin')->group(function () {
    Route::middleware(['throttle:100,1'])->group(function () {
        Route::get('ping', function () {
            return response()->json([
                'status' => 'ok',
                'time'   => Carbon::now()->toDateTimeString(),
            ]);
        });

        Route::get('timestamp', function () {
            return response(Carbon::now()->timestamp);
        });
    });

    Route::prefix('admin')->middleware('auth.admin')->group(function () {
        Route::prefix('users')->group(function () {
            Route::middleware('filter.json')->group(function () {
                Route::patch('/{user_id}', [UserController::class, 'UpdateUser']);
            });

            Route::post('/', [UserController::class, 'CreateUser']);
            Route::delete('/{user_id}', [UserController::class, 'DeleteUser']);
            Route::get('/{user_id}', [UserController::class, 'GetUser']);

            Route::prefix('/{user_id}/')->group(function () {
                Route::prefix('subscriptions')->group(function () {
                    Route::middleware('filter.json')->group(function () {
                        Route::post('/', [SubscriptionController::class, 'CreateSubscription']);
                        Route::post('/{subscription_id}', [SubscriptionController::class, 'MutateSubscription']);
                        Route::patch('/{subscription_id}/cancel', [SubscriptionController::class, 'CancelSubscription']);
                    });

                    Route::patch('/{subscription_id}/uncancel', [SubscriptionController::class, 'UncancelSubscription']);
                    Route::delete('/{subscription_id}', [SubscriptionController::class, 'DeleteSubscription']);
                    Route::get('/', [SubscriptionController::class, 'GetSubscriptions']);
                    Route::get('/{subscription_id}', [SubscriptionController::class, 'GetSubscription']);
                    Route::get('/{subscription_id}/logs', [SubscriptionController::class, 'GetSubscriptionLogs']);
                    Route::get('/{subscription_id}/usages', [SubscriptionController::class, 'GetSubscriptionUsages']);
                });

                Route::prefix('personal-tokens')->group(function () {
                    Route::middleware('filter.json')->group(function () {
                        Route::post('/', [PersonalTokenController::class, 'CreatePersonalToken']);
                        Route::patch('/{token_id}', [PersonalTokenController::class, 'UpdatePersonalToken']);
                    });

                    Route::delete('/{token_id}', [PersonalTokenController::class, 'DeletePersonalToken']);
                    Route::post('/{token_id}/reset', [PersonalTokenController::class, 'ResetPersonalToken']);
                    Route::get('/{token_id}', [PersonalTokenController::class, 'GetPersonalToken']);
                    Route::get('/', [PersonalTokenController::class, 'GetPersonalTokens']);
                    Route::post('/sync', [PersonalTokenController::class, 'Sync']);
                });
            });
        });

        Route::prefix('plans')->group(function () {
            Route::middleware('filter.json')->group(function () {
                Route::post('/', [PlanController::class, 'CreatePlan']);
                Route::patch('/{plan_id}', [PlanController::class, 'UpdatePlan']);
            });

            Route::delete('/{plan_id}', [PlanController::class, 'DeletePlan']);
            Route::get('/', [PlanController::class, 'GetPlans']);
            Route::get('/{plan_id}', [PlanController::class, 'GetPlan']);
        });

        Route::prefix('logs')->group(function () {
            Route::get('/', [AdminLogController::class, 'GetAdminLogs']);
            Route::get('/{log_id}', [AdminLogController::class, 'GetAdminLog']);
        });

        Route::prefix('user-logs')->group(function () {
            Route::get('/', [UserLogController::class, 'GetUserLogs']);
            Route::get('/{log_id}', [UserLogController::class, 'GetUserLog']);
        });
    });
});
