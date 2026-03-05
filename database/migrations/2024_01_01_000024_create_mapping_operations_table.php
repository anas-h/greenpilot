<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->string('mot_cle');
            $table->foreignId('type_dechet_id')->constrained('types_dechets');
            $table->decimal('quantite_defaut', 10, 3);
            $table->enum('unite', ['kg', 'litres', 'unites']);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_operations');
    }
};
