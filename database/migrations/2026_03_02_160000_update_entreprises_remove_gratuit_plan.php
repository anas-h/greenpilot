<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing 'gratuit' entreprises to 'standard' with 14-day trial
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('entreprises')
                ->where('plan', 'gratuit')
                ->update([
                    'plan' => 'standard',
                    'trial_ends_at' => now()->addDays(14),
                    'max_garages' => 3,
                    'max_users' => 15,
                ]);
        } else {
            DB::table('entreprises')
                ->where('plan', 'gratuit')
                ->update([
                    'plan' => 'standard',
                    'trial_ends_at' => now()->addDays(14),
                    'max_garages' => DB::raw('GREATEST(max_garages, 3)'),
                    'max_users' => DB::raw('GREATEST(max_users, 15)'),
                ]);

            DB::statement('ALTER TABLE entreprises DROP CONSTRAINT IF EXISTS entreprises_plan_check');
            DB::statement("ALTER TABLE entreprises ALTER COLUMN plan SET DEFAULT 'standard'");
            DB::statement("ALTER TABLE entreprises ADD CONSTRAINT entreprises_plan_check CHECK (plan IN ('standard', 'premium'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE entreprises DROP CONSTRAINT IF EXISTS entreprises_plan_check');
        DB::statement("ALTER TABLE entreprises ALTER COLUMN plan SET DEFAULT 'gratuit'");
        DB::statement("ALTER TABLE entreprises ADD CONSTRAINT entreprises_plan_check CHECK (plan IN ('gratuit', 'standard', 'premium'))");
    }
};
