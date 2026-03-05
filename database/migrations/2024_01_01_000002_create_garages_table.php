<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('nom');
            $table->string('siret_etablissement', 14)->nullable();
            $table->string('adresse');
            $table->string('code_postal', 10);
            $table->string('ville');
            $table->string('telephone', 20)->nullable();
            $table->integer('surface_atelier')->nullable();
            $table->enum('regime_icpe', ['non_classe', 'declaration', 'enregistrement'])->nullable();
            $table->json('activites')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
