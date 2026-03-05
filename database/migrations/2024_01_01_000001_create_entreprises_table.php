<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale');
            $table->string('siret', 14)->unique();
            $table->string('forme_juridique')->nullable();
            $table->string('adresse');
            $table->string('code_postal', 10);
            $table->string('ville');
            $table->string('telephone', 20)->nullable();
            $table->string('email_contact')->nullable();
            $table->string('logo_path')->nullable();
            $table->enum('plan', ['gratuit', 'standard', 'premium'])->default('gratuit');
            $table->datetime('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
