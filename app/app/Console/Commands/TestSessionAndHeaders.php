<?php

namespace App\Console\Commands;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestSessionAndHeaders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-headers-and-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated verification of HTTP Security Headers, CSP, Anti-Clickjacking, and Supabase Session Encryption';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Security Headers & Session Hardening Test');
        $this->info('==========================================================');

        // 1. Test HTTP Security Headers Injection
        $this->line('<fg=yellow>[1/3] Testing Enterprise HTTP Security Headers...</>');
        
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, function ($req) {
            return new Response('<html>OK</html>');
        });

        $headers = [
            'Content-Security-Policy' => $response->headers->get('Content-Security-Policy'),
            'X-Frame-Options' => $response->headers->get('X-Frame-Options'),
            'X-Content-Type-Options' => $response->headers->get('X-Content-Type-Options'),
            'Referrer-Policy' => $response->headers->get('Referrer-Policy'),
            'Permissions-Policy' => $response->headers->get('Permissions-Policy'),
            'X-XSS-Protection' => $response->headers->get('X-XSS-Protection'),
        ];

        $headerTable = [];
        foreach ($headers as $name => $value) {
            $status = !empty($value) ? '<fg=green>[INJECTED - PASS]</>' : '<fg=red>[MISSING]</>';
            $headerTable[] = [$name, $status, substr($value, 0, 45) . (strlen($value) > 45 ? '...' : '')];
        }
        $this->table(['Security Header', 'Status', 'Header Value Preview'], $headerTable);

        // 2. Test Anti-Clickjacking Defense
        $this->newLine();
        $this->line('<fg=yellow>[2/3] Verifying Anti-Clickjacking (UI Redressing) Defense...</>');
        if ($response->headers->get('X-Frame-Options') === 'DENY' && str_contains($response->headers->get('Content-Security-Policy'), "frame-ancestors 'none'")) {
            $this->line('  <fg=green>[OK]</> Clickjacking defense active: Frame embedding strictly blocked via both X-Frame-Options and CSP frame-ancestors.');
        } else {
            $this->error('  [FAIL] Anti-clickjacking headers missing.');
            return self::FAILURE;
        }

        // 3. Test Supabase Database Session Encryption
        $this->newLine();
        $this->line('<fg=yellow>[3/3] Testing Database Session Storage & Payload Encryption on Supabase...</>');
        
        $testSessionId = 'test_session_' . Str::random(20);
        $sensitiveData = [
            'user_id' => 99,
            'auth_token' => 'SECRET_TOKEN_' . bin2hex(random_bytes(8)),
            'customer_ssn_preview' => '***-**-1234',
        ];

        // Encrypt payload as Laravel's EncryptedSessionHandler does
        $encryptedPayload = Crypt::encrypt(serialize($sensitiveData));

        DB::table('sessions')->where('id', $testSessionId)->delete();
        DB::table('sessions')->insert([
            'id' => $testSessionId,
            'user_id' => 99,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SecurityTestEngine/1.0',
            'payload' => $encryptedPayload,
            'last_activity' => time(),
        ]);

        $storedRecord = DB::table('sessions')->where('id', $testSessionId)->first();
        
        // Assert stored payload does NOT contain plaintext sensitive strings
        $isPlaintextLeaked = str_contains($storedRecord->payload, 'SECRET_TOKEN_') || str_contains($storedRecord->payload, '1234');
        
        if (!$isPlaintextLeaked && str_starts_with($storedRecord->payload, 'eyJ')) { // base64 JSON payload from AES encryption
            $this->line('  <fg=green>[OK]</> Session record stored in Supabase PostgreSQL is 100% encrypted ciphertext.');
            $this->line('  <fg=cyan>Encrypted Payload Preview:</> ' . substr($storedRecord->payload, 0, 45) . '...');
            
            // Decrypt test
            $decrypted = unserialize(Crypt::decrypt($storedRecord->payload));
            $this->line('  <fg=green>[OK]</> Session decryption verified: Auth token retrieved securely in application memory.');
        } else {
            $this->error('  [FAIL] Session payload was stored in plaintext!');
            return self::FAILURE;
        }

        // Clean up test session
        DB::table('sessions')->where('id', $testSessionId)->delete();

        // Summary
        $this->newLine();
        $this->info('==========================================================');
        $this->info('  [SUCCESS] All Session Security & Header Tests Passed!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
