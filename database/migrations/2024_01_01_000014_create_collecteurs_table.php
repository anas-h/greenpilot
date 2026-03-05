<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collecteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->string('raison_sociale');
            $table->string('siret', 14);
            $table->string('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();
            $table->string('numero_autorisation');
            $table->date('date_validite_autorisation');
            $table->boolean('autorisation_adr')->default(false);
            $table->string('numero_adr')->nullable();
            $table->string('eco_organisme')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_telephone', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('site_web')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collecteurs');
    }
};
