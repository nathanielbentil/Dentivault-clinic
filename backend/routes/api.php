<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupApiController;
use App\Http\Controllers\Api\TwoFactorApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes for Decoupled Pure HTML Frontend
|--------------------------------------------------------------------------
|
| All routes return standard JSON responses and support Bearer Token (Sanctum)
| authentication headers.
|
*/

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:15,1');
    Route::post('two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->middleware('throttle:10,1');
    Route::post('send-email-otp', [AuthController::class, 'sendEmailOtp'])->middleware('throttle:10,1');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Authenticated Routes (Requires Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // Current User Profile, Update & Logout
    Route::get('auth/user', [AuthController::class, 'user']);
    Route::post('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/password', [AuthController::class, 'updatePassword']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Two-Factor Authentication Management
    Route::prefix('2fa')->group(function () {
        Route::get('status', [TwoFactorApiController::class, 'status']);
        Route::post('setup', [TwoFactorApiController::class, 'setup']);
        Route::post('confirm', [TwoFactorApiController::class, 'confirm']);
        Route::post('disable', [TwoFactorApiController::class, 'disable']);
        Route::post('recovery-codes', [TwoFactorApiController::class, 'regenerateRecoveryCodes']);
    });

    // Administrator Zone (Protected by role:Admin)
    Route::prefix('admin')->middleware('role:Admin,SuperAdmin')->group(function () {
        Route::get('users', [AdminApiController::class, 'users']);
        Route::post('assign-role', [AdminApiController::class, 'assignRole']);
        Route::get('audit-logs', [AdminApiController::class, 'auditLogs']);
        Route::get('verify-audit-chain', [AdminApiController::class, 'verifyAuditChain']);
        Route::post('simulate-tampering', [AdminApiController::class, 'simulateTampering']);

        // 3-2-1 Backups & Disaster Recovery Management
        Route::get('backups', [BackupApiController::class, 'list']);
        Route::post('backups/create', [BackupApiController::class, 'create']);
        Route::post('backups/drill', [BackupApiController::class, 'drill']);
    });
});
