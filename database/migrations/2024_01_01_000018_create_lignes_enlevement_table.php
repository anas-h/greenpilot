<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_enlevement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enlevement_id')->constrained('enlevements')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->constrained('types_dechets');
            $table->foreignId('conteneur_id')->nullable()->constrained('conteneurs')->nullOnDelete();
            $table->decimal('quantite_prevue', 10, 3);
            $table->decimal('quantite_effective', 10, 3)->nullable();
            $table->enum('unite', ['kg', 'litres', 'unites']);
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->boolean('est_rachat')->default(false);
            $table->decimal('montant', 10, 2)->default(0);
            $table->unsignedBigInteger('bordereau_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_enlevement');
    }
};
