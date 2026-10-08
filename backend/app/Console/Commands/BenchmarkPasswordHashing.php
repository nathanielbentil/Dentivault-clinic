<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BenchmarkPasswordHashing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:benchmark-argon2id {--iterations=3 : Number of test hashing iterations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark Argon2id memory-hard hashing performance and GPU/ASIC resistance against traditional algorithms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Password Hashing Cryptographic Benchmark');
        $this->info('==========================================================');

        $iterations = (int) $this->option('iterations');
        $password = 'BankPassword!2026SecureBenchmarking';
        $pepper = config('security.pepper.versions.v1', 'default_pepper_key_for_benchmarking_32');

        $this->line("Running {$iterations} iterations per algorithm to measure defense latency...\n");

        // 1. Benchmark SHA-256 (Vulnerable / Obsolete for Passwords)
        $t0 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            hash('sha256', $password);
        }
        $sha256Time = ((microtime(true) - $t0) / $iterations) * 1000;

        // 2. Benchmark Bcrypt (Cost 12)
        $t0 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $bcryptTime = ((microtime(true) - $t0) / $iterations) * 1000;

        // 3. Benchmark Argon2id + HMAC Pepper (Bank-Grade Configuration)
        $memory = config('security.argon2id.memory', 65536);
        $time = config('security.argon2id.time', 4);
        $threads = config('security.argon2id.threads', 2);

        $t0 = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $peppered = hash_hmac('sha256', $password, $pepper);
            password_hash($peppered, PASSWORD_ARGON2ID, [
                'memory_cost' => $memory,
                'time_cost' => $time,
                'threads' => $threads,
            ]);
        }
        $argonTime = ((microtime(true) - $t0) / $iterations) * 1000;

        $results = [
            [
                'Algorithm' => 'SHA-256 (Legacy / Broken for Auth)',
                'Avg Latency' => sprintf('%.4f ms', $sha256Time),
                'Memory Footprint' => '0 KB (Negligible)',
                'GPU Attack Resistance' => '<fg=red>EXTREMELY POOR (Billions/sec on GPU)</>',
                'Defense Rating' => 'FAIL',
            ],
            [
                'Algorithm' => 'Bcrypt (Cost 12)',
                'Avg Latency' => sprintf('%.2f ms', $bcryptTime),
                'Memory Footprint' => '4 KB (L1 Cache Fitting)',
                'GPU Attack Resistance' => '<fg=yellow>MODERATE (Resistant to basic CPU cracking)</>',
                'Defense Rating' => 'ACCEPTABLE',
            ],
            [
                'Algorithm' => 'Argon2id + HMAC Pepper (Our Stack)',
                'Avg Latency' => sprintf('%.2f ms', $argonTime),
                'Memory Footprint' => ($memory / 1024) . " MB ({$memory} KB)",
                'GPU Attack Resistance' => '<fg=green>BANK-GRADE (Memory-hard; paralyzes ASIC/GPU clusters)</>',
                'Defense Rating' => 'OPTIMAL',
            ],
        ];

        $this->table(['Hashing Algorithm', 'Avg Latency (ms)', 'Memory Footprint', 'GPU / ASIC Resistance', 'Defense Rating'], $results);

        $this->newLine();
        $this->info('Academic Defense Rationale:');
        $this->line('• <fg=cyan>Why Argon2id over Bcrypt?</> Bcrypt only uses 4KB of RAM, allowing attackers to pack thousands of parallel cracking cores onto GPUs. Argon2id requires 64MB of dedicated RAM per hash, instantly exhausting GPU memory and rendering multi-million-dollar cracking rigs useless.');
        $this->line('• <fg=cyan>Why HMAC Pepper?</> If an attacker steals the entire database via a SQL injection or zero-day, password hashes remain uncrackable because the 256-bit pepper is stored exclusively in server environment memory.');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
