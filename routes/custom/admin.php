<?php

use App\Http\Controllers\Api\V1\Admin;

Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('travels', [Admin\TravelController::class, 'store']);
            Route::post('travels/{travel}/tours', [Admin\TourController::class, 'store']);
        });

        Route::put('travels/{travel}', [Admin\TravelController::class, 'update']);
    });
