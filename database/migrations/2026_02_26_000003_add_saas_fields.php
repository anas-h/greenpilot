<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->boolean('actif')->default(true)->after('trial_ends_at');
            $table->integer('max_garages')->default(1)->after('actif');
            $table->integer('max_users')->default(5)->after('max_garages');
            $table->index('plan');
        });

        // Add super_admin to the role check — PostgreSQL enum alteration
        // The 'role' column is a varchar, not a native PG enum, so no ALTER TYPE needed
        // Laravel stores role as a string column, so super_admin is already a valid value
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropIndex(['plan']);
            $table->dropColumn(['actif', 'max_garages', 'max_users']);
        });
    }
};
