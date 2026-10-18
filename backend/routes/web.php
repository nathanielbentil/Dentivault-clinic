<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditorController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Information Security Report (PDF & Print View - accessible for evaluation)
Route::get('/security/report/pdf', [\App\Http\Controllers\ReportController::class, 'downloadSecurityPdf'])->name('security.report.pdf');
Route::get('/security/report/print', [\App\Http\Controllers\ReportController::class, 'viewSecurityReport'])->name('security.report.print');

Route::middleware(['auth'])->group(function () {
    // 1. Customer / Clinical Staff Dashboard
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    Route::post('/sql/test-parameterized', [CustomerController::class, 'testParameterizedQuery'])->name('sql.test');
    Route::post('/profile/update-name', [CustomerController::class, 'updateName'])->name('profile.update-name');
    Route::post('/profile/update-email', [CustomerController::class, 'updateEmail'])->name('profile.update-email');
    Route::post('/profile/update-password', [CustomerController::class, 'updatePassword'])->name('profile.update-password');

    // 1.1 Biometric Hardware Passkey Registration & Management
    Route::get('/biometric/register-challenge', [\App\Http\Controllers\BiometricAuthController::class, 'registerChallenge'])->name('biometric.register-challenge');
    Route::post('/biometric/register-verify', [\App\Http\Controllers\BiometricAuthController::class, 'registerVerify'])->name('biometric.register-verify');
    Route::delete('/biometric/credentials/{credential}', [\App\Http\Controllers\BiometricAuthController::class, 'destroy'])->name('biometric.destroy');

    // 1.2 Dedicated Specialized Clinical & Security Workstations (Separate Pages)
    Route::get('/doctor/prescriptions', [\App\Http\Controllers\ClinicalStationController::class, 'prescriptions'])->name('doctor.prescriptions');
    Route::get('/nurse/vitals', [\App\Http\Controllers\ClinicalStationController::class, 'vitals'])->name('nurse.vitals');
    Route::get('/pharmacy/dispensary', [\App\Http\Controllers\ClinicalStationController::class, 'dispensary'])->name('pharmacy.dispensary');
    Route::get('/security/telemetry', [\App\Http\Controllers\ClinicalStationController::class, 'telemetry'])->name('security.telemetry');
    Route::post('/patients/admit', [\App\Http\Controllers\ClinicalStationController::class, 'admitPatient'])->name('patients.admit');
    Route::post('/patients/{id}/discharge', [\App\Http\Controllers\ClinicalStationController::class, 'dischargePatient'])->name('patients.discharge');
    Route::delete('/patients/{id}', [\App\Http\Controllers\ClinicalStationController::class, 'softDeletePatient'])->name('patients.destroy');
    Route::post('/patients/{id}/restore', [\App\Http\Controllers\ClinicalStationController::class, 'restorePatient'])->name('patients.restore');

    // 2. Chief Admin & Managing Director Operations Suite
    Route::prefix('admin')->name('admin.')->middleware(['role:Admin,SuperAdmin,Manager'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::post('/users/{user}/assign-role', [AdminController::class, 'assignRole'])->name('users.assign-role');
        Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [AdminController::class, 'restoreUser'])->name('users.restore');
        Route::put('/users/{id}', [AdminController::class, 'updateStaff'])->name('users.update');
        Route::post('/users/{id}/reset-password', [AdminController::class, 'resetStaffPassword'])->name('users.reset-password');
        Route::post('/users/{id}/unlock', [AdminController::class, 'unlockStaff'])->name('users.unlock');
        Route::post('/config/{id}/rollback', [AdminController::class, 'rollbackConfiguration'])->name('config.rollback');
        Route::get('/backups', [AdminController::class, 'backups'])->name('backups');
        Route::post('/backups/trigger', [AdminController::class, 'triggerBackup'])->name('backups.trigger');
        Route::post('/backups/drill', [AdminController::class, 'triggerDrill'])->name('backups.drill');
    });

    // 3. Security & HIPAA Audit Forensics Hub
    Route::prefix('audit')->name('audit.')->middleware(['role:Admin,SuperAdmin,SecurityAuditor,Auditor'])->group(function () {
        Route::get('/dashboard', [AuditorController::class, 'index'])->name('dashboard');
        Route::get('/verify-integrity', [AuditorController::class, 'verifyIntegrity'])->name('verify-integrity');
    });

    // 4. Practice Operations Portal
    Route::prefix('branch')->name('branch.')->middleware(['role:Manager,BranchManager,Admin,SuperAdmin'])->group(function () {
        Route::get('/dashboard', [BranchController::class, 'index'])->name('dashboard');
        Route::post('/customers/{user}/unlock', [BranchController::class, 'unlockCustomer'])->name('customers.unlock');
    });
});

require __DIR__.'/auth.php';
