<?php

use App\Http\Controllers\Api\AuthController;
use App\Admin\Controllers\SafeguardingIncidentController;
use App\Http\Middleware\LogActivity;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,15');

require __DIR__ . '/front-end-api.php';



Route::middleware(['auth:sanctum', LogActivity::class, 'active_user'])->group(function () {
    Route::get('safeguarding/incidents/{incident}', [SafeguardingIncidentController::class, 'apiShow'])->name('api.safeguarding.incidents.show');
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('profile/update-photo', [AuthController::class, 'updateProfilePhoto'])->name('profile.updatePhoto');
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});
