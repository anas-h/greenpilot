<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add entreprise_id column (nullable temporarily)
        Schema::table('config_trackdechets', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->after('id')->constrained('entreprises')->cascadeOnDelete();
        });

        // 2. Populate entreprise_id from garages table
        DB::statement('
            UPDATE config_trackdechets
            SET entreprise_id = (
                SELECT garages.entreprise_id
                FROM garages
                WHERE garages.id = config_trackdechets.garage_id
            )
        ');

        // 3. Deduplicate: keep the most recent config per entreprise
        $duplicates = DB::table('config_trackdechets')
            ->select('entreprise_id')
            ->groupBy('entreprise_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('entreprise_id');

        foreach ($duplicates as $entrepriseId) {
            $keepId = DB::table('config_trackdechets')
                ->where('entreprise_id', $entrepriseId)
                ->orderByDesc('updated_at')
                ->value('id');

            DB::table('config_trackdechets')
                ->where('entreprise_id', $entrepriseId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        // 4. Drop old columns and add unique constraint
        Schema::table('config_trackdechets', function (Blueprint $table) {
            $table->dropForeign(['garage_id']);
            $table->dropColumn(['garage_id', 'siret']);
            $table->unique('entreprise_id');
        });

        // 5. Make entreprise_id non-nullable
        Schema::table('config_trackdechets', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('config_trackdechets', function (Blueprint $table) {
            $table->dropForeign(['entreprise_id']);
            $table->dropUnique(['entreprise_id']);
            $table->dropColumn('entreprise_id');
            $table->foreignId('garage_id')->unique()->constrained('garages')->cascadeOnDelete();
            $table->string('siret', 14)->after('garage_id');
        });
    }
};
