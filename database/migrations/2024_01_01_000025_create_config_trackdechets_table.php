<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_trackdechets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->unique()->constrained('garages')->cascadeOnDelete();
            $table->string('siret', 14);
            $table->text('api_token');
            $table->enum('environnement', ['sandbox', 'production']);
            $table->boolean('actif')->default(false);
            $table->datetime('derniere_sync_reussie')->nullable();
            $table->text('derniere_erreur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_trackdechets');
    }
};
