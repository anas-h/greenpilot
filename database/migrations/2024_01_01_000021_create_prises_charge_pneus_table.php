<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prises_charge_pneus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->string('nom_particulier');
            $table->string('telephone', 20)->nullable();
            $table->integer('quantite');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prises_charge_pneus');
    }
};
