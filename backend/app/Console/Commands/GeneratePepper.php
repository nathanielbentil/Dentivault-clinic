<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePepper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:generate-pepper {--show : Display the key instead of modifying files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a cryptographically secure 256-bit HMAC password pepper';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pepper = bin2hex(random_bytes(32)); // 64 hex characters (256-bit entropy)

        if ($this->option('show')) {
            $this->line('<comment>' . $pepper . '</comment>');
            return self::SUCCESS;
        }

        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            $this->error('.env file does not exist. Please copy .env.example to .env first.');
            return self::FAILURE;
        }

        $envContent = file_get_contents($envFile);

        if (str_contains($envContent, 'PEPPER_V1=')) {
            $envContent = preg_replace('/^PEPPER_V1=.*$/m', 'PEPPER_V1=' . $pepper, $envContent);
        } else {
            $envContent .= "\nPEPPER_V1=" . $pepper;
        }

        if (!str_contains($envContent, 'CURRENT_PEPPER_VERSION=')) {
            $envContent .= "\nCURRENT_PEPPER_VERSION=v1";
        }

        file_put_contents($envFile, $envContent);

        $this->info('Security pepper (256-bit HMAC) generated and written to .env successfully.');
        $this->line('<fg=cyan>Pepper Version:</> v1');
        $this->line('<fg=yellow>Defense Note:</> This pepper acts as a server-side salt layer. Passwords cannot be cracked offline even if the database is breached without this value.');

        return self::SUCCESS;
    }
}
