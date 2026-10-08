<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditorController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    // 1. Customer Banking Dashboard
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');

    // 2. SuperAdmin Executive Zone
    Route::prefix('admin')->name('admin.')->middleware(['role:SuperAdmin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/users/{user}/assign-role', [AdminController::class, 'assignRole'])->name('users.assign-role');
        Route::get('/backups', [AdminController::class, 'backups'])->name('backups');
        Route::post('/backups/trigger', [AdminController::class, 'triggerBackup'])->name('backups.trigger');
        Route::post('/backups/drill', [AdminController::class, 'triggerDrill'])->name('backups.drill');
    });

    // 3. Security Auditor Forensics Hub
    Route::prefix('audit')->name('audit.')->middleware(['role:SecurityAuditor,SuperAdmin'])->group(function () {
        Route::get('/dashboard', [AuditorController::class, 'index'])->name('dashboard');
        Route::get('/verify-integrity', [AuditorController::class, 'verifyIntegrity'])->name('verify-integrity');
    });

    // 4. Branch Manager Operations Portal
    Route::prefix('branch')->name('branch.')->middleware(['role:BranchManager,SuperAdmin'])->group(function () {
        Route::get('/dashboard', [BranchController::class, 'index'])->name('dashboard');
        Route::post('/customers/{user}/unlock', [BranchController::class, 'unlockCustomer'])->name('customers.unlock');
    });
});

require __DIR__.'/auth.php';
