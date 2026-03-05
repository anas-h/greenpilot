<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collecteur_type_dechet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collecteur_id')->constrained('collecteurs')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->constrained('types_dechets')->cascadeOnDelete();
            $table->decimal('prix_unitaire', 10, 2)->nullable();
            $table->decimal('prix_forfaitaire', 10, 2)->nullable();
            $table->boolean('est_rachat')->default(false);
            $table->date('date_effet')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collecteur_type_dechet');
    }
};
