<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule Automated 3-2-1 Encrypted Database Backup (Nightly at 02:00 UTC)
Schedule::command('backup:create-local')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \App\Models\AuditLog::record(
            eventType: 'backup.failed',
            description: 'Scheduled nightly backup failed to execute.',
            severity: 'critical'
        );
    });
