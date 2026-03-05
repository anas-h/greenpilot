<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->string('type');
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'critique']);
            $table->string('message');
            $table->text('detail')->nullable();
            $table->string('entite_type')->nullable();
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->boolean('lue')->default(false);
            $table->boolean('resolue')->default(false);
            $table->foreignId('resolue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('date_resolution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
