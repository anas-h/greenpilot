<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garage_type_dechet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->constrained('types_dechets')->cascadeOnDelete();
            $table->boolean('actif')->default(true);
            $table->decimal('quantite_defaut', 10, 3)->nullable();

            $table->unique(['garage_id', 'type_dechet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_type_dechet');
    }
};
