<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_conteneurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteneur_id')->constrained('conteneurs')->cascadeOnDelete();
            $table->enum('type_evenement', ['ajout', 'retrait', 'enlevement', 'correction', 'vidange']);
            $table->decimal('quantite', 10, 3);
            $table->decimal('niveau_avant', 10, 2);
            $table->decimal('niveau_apres', 10, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->datetime('date_evenement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_conteneurs');
    }
};
