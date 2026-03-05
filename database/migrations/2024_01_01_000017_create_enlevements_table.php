<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enlevements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->foreignId('collecteur_id')->constrained('collecteurs');
            $table->date('date_prevue');
            $table->datetime('date_effective')->nullable();
            $table->enum('statut', ['brouillon', 'planifie', 'en_cours', 'complete', 'partiel', 'annule']);
            $table->string('numero_bon_enlevement')->nullable();
            $table->string('bon_enlevement_path')->nullable();
            $table->decimal('cout_total', 10, 2)->default(0);
            $table->decimal('revenu_total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('recurrence', ['hebdomadaire', 'bimensuel', 'mensuel', 'trimestriel'])->nullable();
            $table->date('prochaine_date')->nullable();
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enlevements');
    }
};
