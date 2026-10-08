<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Services\AuditLoggerService;
use Tests\TestCase;

class AuditTrailSecurityTest extends TestCase
{
    public function test_audit_log_records_append_with_valid_hash_chain(): void
    {
        $log = AuditLog::record(
            eventType: 'test.verification_event',
            description: 'Automated test suite audit verification entry.',
            severity: 'info'
        );

        $this->assertNotNull($log->id);
        $this->assertNotEmpty($log->log_hash);
        $this->assertEquals(64, strlen($log->log_hash));

        $report = AuditLoggerService::verifyChainIntegrity();
        $this->assertTrue($report['intact'], 'Cryptographic hash chain must be 100% intact.');
    }

    public function test_audit_logs_are_immutable(): void
    {
        $log = AuditLog::latest('id')->first();
        $this->assertNotNull($log);

        $this->expectException(\RuntimeException::class);
        $log->update(['description' => 'Unauthorized alteration attempt']);
    }
}
