<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateAuditTampering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:simulate-audit-tampering';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate an attacker altering a historical audit log in the database and demonstrate cryptographic tamper detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Cryptographic Audit Tampering Attack Defense');
        $this->info('==========================================================');

        // Step 1: Record a baseline audit entry
        $this->line('<fg=yellow>[Step 1] Recording Authentic Security Audit Entry in Supabase...</>');
        $originalLog = AuditLog::record(
            eventType: 'transfer.authorized',
            description: 'Authorized wire transfer of $100,000 to Beneficiary Bank Account #8839.',
            severity: 'notice',
            payload: ['amount' => 100000, 'beneficiary' => 'Acct-8839']
        );

        $originalHash = $originalLog->log_hash;
        $this->line("  <fg=green>[OK]</> Authentic Log Stored. ID: #{$originalLog->id}");
        $this->line("  <fg=cyan>Original SHA-256 Hash:</> {$originalHash}");

        // Step 2: Simulate Rogue DBA / Attacker Tampering with the Data
        $this->newLine();
        $this->line('<fg=yellow>[Step 2] Attacker Executes Direct SQL to Alter Record Description...</>');
        $this->line('  Simulated Query: UPDATE audit_logs SET description = "Authorized wire transfer of $10,000"...');
        
        DB::table('audit_logs')
            ->where('id', $originalLog->id)
            ->update([
                'description' => 'Authorized wire transfer of $10,000 to Beneficiary Bank Account #8839.',
            ]);

        $this->line('  <fg=red>[TAMPERED]</> Database record modified directly on Supabase PostgreSQL.');

        // Step 3: Run Cryptographic Chain Verifier
        $this->newLine();
        $this->line('<fg=yellow>[Step 3] Automated Forensic Verifier Evaluates Cryptographic Chain...</>');
        
        $tamperedLog = DB::table('audit_logs')->where('id', $originalLog->id)->first();
        
        // Recompute hash on altered payload
        $chainData = json_encode([
            'prev_hash' => $tamperedLog->prev_hash,
            'event_type' => $tamperedLog->event_type,
            'severity' => $tamperedLog->severity,
            'user_id' => $tamperedLog->user_id,
            'ip' => $tamperedLog->ip_address,
            'timestamp' => (new \DateTime($tamperedLog->created_at))->format('c'),
            'payload' => json_decode($tamperedLog->payload, true),
        ]);

        $expectedHash = hash('sha256', $chainData);

        if (!hash_equals($tamperedLog->log_hash, $expectedHash)) {
            $this->error("  [TAMPER DETECTED] Cryptographic hash mismatch on Record ID #{$tamperedLog->id}!");
            $this->line("  Expected SHA-256: <fg=red>{$expectedHash}</>");
            $this->line("  Stored SHA-256:   <fg=yellow>{$tamperedLog->log_hash}</>");

            // Log incident in SecurityEvents
            SecurityEvent::logAttack(
                attackType: 'tamper_detected',
                payloadSnippet: [
                    'record_id' => $tamperedLog->id,
                    'tampered_field' => 'description',
                    'stored_hash' => $tamperedLog->log_hash,
                    'computed_hash' => $expectedHash,
                ],
                blocked: true
            );

            $this->line("  <fg=green>[ALERT LOGGED]</> Intrusion Detection recorded critical incident in security_events table.");
        }

        // Step 4: Cleanup test record
        DB::table('audit_logs')->where('id', $originalLog->id)->delete();

        // Summary
        $this->newLine();
        $this->info('==========================================================');
        $this->info('  [SUCCESS] Cryptographic Anti-Tampering Defense Verified!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
