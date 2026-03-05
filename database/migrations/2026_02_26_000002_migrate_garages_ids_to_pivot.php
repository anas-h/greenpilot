<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->whereNotNull('garages_ids')->get();

        foreach ($users as $user) {
            $garageIds = json_decode($user->garages_ids, true);

            if (! is_array($garageIds)) {
                continue;
            }

            foreach ($garageIds as $garageId) {
                // Only insert if the garage actually exists
                $garageExists = DB::table('garages')->where('id', $garageId)->exists();
                if (! $garageExists) {
                    continue;
                }

                DB::table('user_garage')->insertOrIgnore([
                    'user_id' => $user->id,
                    'garage_id' => $garageId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Reverse: write pivot data back to garages_ids JSON
        $pivotData = DB::table('user_garage')
            ->select('user_id', DB::raw('json_agg(garage_id) as garage_ids'))
            ->groupBy('user_id')
            ->get();

        foreach ($pivotData as $row) {
            DB::table('users')
                ->where('id', $row->user_id)
                ->update(['garages_ids' => $row->garage_ids]);
        }
    }
};
