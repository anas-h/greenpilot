<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteneurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->nullable()->constrained('types_dechets')->nullOnDelete();
            $table->string('nom');
            $table->string('code_qr')->unique();
            $table->decimal('capacite', 10, 2);
            $table->enum('unite', ['kg', 'litres', 'unites']);
            $table->decimal('niveau_actuel', 10, 2)->default(0);
            $table->string('emplacement')->nullable();
            $table->boolean('mixte')->default(false);
            $table->date('date_dernier_enlevement')->nullable();
            $table->date('date_mise_en_service')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteneurs');
    }
};
