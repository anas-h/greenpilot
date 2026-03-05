<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_dechets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises')->nullOnDelete();
            $table->string('code_europeen', 15);
            $table->string('denomination');
            $table->boolean('dangereux');
            $table->enum('unite_mesure', ['kg', 'litres', 'unites']);
            $table->enum('categorie', [
                'huile', 'filtre', 'batterie', 'pneu', 'solvant', 'absorbant',
                'emballage', 'metal', 'verre', 'plastique', 'fluide', 'peinture', 'autre',
            ]);
            $table->string('filiere_rep')->nullable();
            $table->json('pictogrammes_danger')->nullable();
            $table->text('consignes_stockage')->nullable();
            $table->text('consignes_tri')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_dechets');
    }
};
