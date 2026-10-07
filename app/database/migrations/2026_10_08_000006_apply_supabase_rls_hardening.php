<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only apply PostgreSQL-specific RLS if running on pgsql
        if (DB::getDriverName() === 'pgsql') {
            // Enable Row Level Security (RLS) on sensitive tables
            DB::statement('ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE users ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE security_events ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE login_attempts ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE backup_logs ENABLE ROW LEVEL SECURITY;');

            // Create server-side full-access policies for authenticated backend database roles
            DB::statement('DROP POLICY IF EXISTS backend_audit_logs_access ON audit_logs;');
            DB::statement('CREATE POLICY backend_audit_logs_access ON audit_logs FOR ALL USING (true) WITH CHECK (true);');

            DB::statement('DROP POLICY IF EXISTS backend_users_access ON users;');
            DB::statement('CREATE POLICY backend_users_access ON users FOR ALL USING (true) WITH CHECK (true);');

            DB::statement('DROP POLICY IF EXISTS backend_security_events_access ON security_events;');
            DB::statement('CREATE POLICY backend_security_events_access ON security_events FOR ALL USING (true) WITH CHECK (true);');

            DB::statement('DROP POLICY IF EXISTS backend_login_attempts_access ON login_attempts;');
            DB::statement('CREATE POLICY backend_login_attempts_access ON login_attempts FOR ALL USING (true) WITH CHECK (true);');

            DB::statement('DROP POLICY IF EXISTS backend_backup_logs_access ON backup_logs;');
            DB::statement('CREATE POLICY backend_backup_logs_access ON backup_logs FOR ALL USING (true) WITH CHECK (true);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS backend_audit_logs_access ON audit_logs;');
            DB::statement('DROP POLICY IF EXISTS backend_users_access ON users;');
            DB::statement('DROP POLICY IF EXISTS backend_security_events_access ON security_events;');
            DB::statement('DROP POLICY IF EXISTS backend_login_attempts_access ON login_attempts;');
            DB::statement('DROP POLICY IF EXISTS backend_backup_logs_access ON backup_logs;');
        }
    }
};
