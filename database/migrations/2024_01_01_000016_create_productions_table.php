<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->foreignId('type_dechet_id')->constrained('types_dechets');
            $table->foreignId('conteneur_id')->nullable()->constrained('conteneurs')->nullOnDelete();
            $table->decimal('quantite', 10, 3);
            $table->enum('unite', ['kg', 'litres', 'unites']);
            $table->date('date_production');
            $table->enum('source_type', ['manuelle', 'scan_qr', 'import_or', 'correction'])->nullable();
            $table->string('reference_externe')->nullable();
            $table->string('vehicule_info')->nullable();
            $table->foreignId('employe_id')->constrained('users');
            $table->boolean('valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('date_validation')->nullable();
            $table->foreignId('correction_de')->nullable()->constrained('productions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
