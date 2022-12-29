<?php

/*
Please DO NOT touch any routes here!!
*/

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

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
                Route::post('/', [\Volistx\FrameworkKernel\Http\Controllers\UserController::class, 'CreateUser']);
                Route::patch('/{user_id}', [\Volistx\FrameworkKernel\Http\Controllers\UserController::class, 'UpdateUser']);
            });

            Route::delete('/{user_id}', [\Volistx\FrameworkKernel\Http\Controllers\UserController::class, 'DeleteUser']);
            Route::get('/{user_id}', [\Volistx\FrameworkKernel\Http\Controllers\UserController::class, 'GetUser']);

            Route::prefix('/{user_id}/')->group(function () {
                Route::prefix('subscriptions')->group(function () {
                    Route::middleware('filter.json')->group(function () {
                        Route::post('/', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'CreateSubscription']);
                        Route::post('/{subscription_id}', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'MutateSubscription']);
                        Route::post('/{subscription_id}/cancel', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'CancelSubscription']);
                    });

                    Route::post('/{subscription_id}/uncancel', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'UncancelSubscription']);
                    Route::delete('/{subscription_id}', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'DeleteSubscription']);
                    Route::get('/', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'GetSubscriptions']);
                    Route::get('/{subscription_id}', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'GetSubscription']);
                    Route::get('/{subscription_id}/logs', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'GetSubscriptionLogs']);
                    Route::get('/{subscription_id}/usages', [\Volistx\FrameworkKernel\Http\Controllers\SubscriptionController::class, 'GetSubscriptionUsages']);
                });

                Route::prefix('personal-tokens')->group(function () {
                    Route::middleware('filter.json')->group(function () {
                        Route::post('/', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'CreatePersonalToken']);
                        Route::patch('/{token_id}', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'UpdatePersonalToken']);
                    });

                    Route::delete('/{token_id}', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'DeletePersonalToken']);
                    Route::patch('/{token_id}/reset', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'ResetPersonalToken']);
                    Route::get('/{token_id}', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'GetPersonalToken']);
                    Route::get('/', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'GetPersonalTokens']);
                    Route::post('/sync', [\Volistx\FrameworkKernel\Http\Controllers\PersonalTokenController::class, 'Sync']);
                });
            });
        });

        Route::prefix('plans')->group(function () {
            Route::middleware('filter.json')->group(function () {
                Route::post('/', [\Volistx\FrameworkKernel\Http\Controllers\PlanController::class, 'CreatePlan']);
                Route::patch('/{plan_id}', [\Volistx\FrameworkKernel\Http\Controllers\PlanController::class, 'UpdatePlan']);
            });

            Route::delete('/{plan_id}', [\Volistx\FrameworkKernel\Http\Controllers\PlanController::class, 'DeletePlan']);
            Route::get('/', [\Volistx\FrameworkKernel\Http\Controllers\PlanController::class, 'GetPlans']);
            Route::get('/{plan_id}', [\Volistx\FrameworkKernel\Http\Controllers\PlanController::class, 'GetPlan']);
        });

        Route::prefix('logs')->group(function () {
            Route::get('/', [\Volistx\FrameworkKernel\Http\Controllers\AdminLogController::class, 'GetAdminLogs']);
            Route::get('/{log_id}', [\Volistx\FrameworkKernel\Http\Controllers\AdminLogController::class, 'GetAdminLog']);
        });

        Route::prefix('user-logs')->group(function () {
            Route::get('/', [\Volistx\FrameworkKernel\Http\Controllers\UserLogController::class, 'GetUserLogs']);
            Route::get('/{log_id}', [\Volistx\FrameworkKernel\Http\Controllers\UserLogController::class, 'GetUserLog']);
        });
    });
});
