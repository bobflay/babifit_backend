<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GymController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\TodayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| babifit API (v1)
|--------------------------------------------------------------------------
| Access tokens carry the 'access' ability; refresh tokens carry 'refresh'.
| Everything except /auth/login and /auth/refresh requires a valid access
| token (Authorization: Bearer <accessToken>).
*/

// --- Auth (public) ---
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);

Route::middleware(['auth:sanctum', 'ability:access'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // --- Profile ---
    Route::get('me', [ProfileController::class, 'show']);
    Route::patch('me', [ProfileController::class, 'update']);

    // --- Today ---
    Route::get('today', [TodayController::class, 'show']);

    // --- Scans (specific routes before {scan}) ---
    Route::get('scans', [ScanController::class, 'index']);
    Route::post('scans', [ScanController::class, 'store']);
    Route::post('scans/upload', [ScanController::class, 'upload']);
    Route::get('scans/parse/{jobId}', [ScanController::class, 'parseStatus']);
    Route::get('scans/{scan}', [ScanController::class, 'show']);
    Route::delete('scans/{scan}', [ScanController::class, 'destroy']);

    // --- Meals ---
    Route::get('meals', [MealController::class, 'index']);
    Route::get('meals/summary', [MealController::class, 'summary']);
    Route::post('meals/recognize', [MealController::class, 'recognize']);
    Route::post('meals/estimate', [MealController::class, 'estimate']);
    Route::post('meals', [MealController::class, 'store']);
    Route::patch('meals/{meal}', [MealController::class, 'update']);
    Route::delete('meals/{meal}', [MealController::class, 'destroy']);

    // --- Activities ---
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/recent', [ActivityController::class, 'recent']);
    Route::get('activities/recommendations', [ActivityController::class, 'recommendations']);
    Route::get('activities/estimate', [ActivityController::class, 'estimate']);
    Route::post('activities', [ActivityController::class, 'store']);
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy']);

    // --- Gym (machine sets, photo + AI calorie estimate) ---
    Route::get('gym', [GymController::class, 'index']);
    Route::post('gym/recognize', [GymController::class, 'recognize']);
    Route::post('gym', [GymController::class, 'store']);
    Route::delete('gym/{gym}', [GymController::class, 'destroy']);

    // --- Progress ---
    Route::get('progress/series', [ProgressController::class, 'series']);
    Route::get('progress/compare', [ProgressController::class, 'compare']);
    Route::get('progress/insights', [ProgressController::class, 'insights']);
});
