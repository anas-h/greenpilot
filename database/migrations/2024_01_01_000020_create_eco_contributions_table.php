<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eco_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->enum('filiere', ['huile', 'pneu', 'batterie']);
            $table->string('eco_organisme');
            $table->integer('annee');
            $table->integer('trimestre');
            $table->decimal('quantite', 10, 3);
            $table->enum('unite', ['kg', 'litres', 'unites']);
            $table->decimal('montant_contribution', 10, 2);
            $table->decimal('montant_collecte', 10, 2)->default(0);
            $table->enum('statut', ['brouillon', 'declare', 'paye']);
            $table->date('date_declaration')->nullable();
            $table->date('date_paiement')->nullable();
            $table->json('enlevement_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_contributions');
    }
};
