<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiches_securite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->constrained('types_dechets');
            $table->string('nom_produit');
            $table->string('fournisseur')->nullable();
            $table->string('fichier_path');
            $table->date('date_emission');
            $table->date('date_validite')->nullable();
            $table->boolean('actif')->default(true);
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches_securite');
    }
};
