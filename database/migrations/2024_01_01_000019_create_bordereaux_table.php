<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordereaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->enum('type_bsd', ['BSDD', 'BSFF', 'BSDA', 'BSVHU']);
            $table->string('trackdechets_id')->nullable();
            $table->string('trackdechets_numero')->nullable();
            $table->enum('statut', [
                'DRAFT', 'SEALED', 'SIGNED_BY_PRODUCER', 'SENT', 'RECEIVED',
                'ACCEPTED', 'REFUSED', 'PROCESSED', 'CANCELED', 'NO_TRACEABILITY',
                'AWAITING_GROUP', 'GROUPED', 'TEMP_STORED', 'TEMP_STORER_ACCEPTED',
                'RESEALED', 'SIGNED_BY_TEMP_STORER', 'RESENT', 'FOLLOWED_WITH_PNTTD',
            ]);
            $table->enum('statut_sync', ['en_attente', 'synchronise', 'erreur'])->default('en_attente');
            $table->string('code_dechet');
            $table->string('denomination_dechet');
            $table->decimal('quantite', 10, 3);
            $table->enum('unite', ['kg', 'litres']);
            $table->foreignId('collecteur_id')->constrained('collecteurs');
            $table->string('transporteur_siret', 14);
            $table->string('transporteur_raison_sociale');
            $table->string('destination_siret', 14);
            $table->string('destination_raison_sociale');
            $table->string('destination_adresse')->nullable();
            $table->string('code_traitement')->nullable();
            $table->string('cap')->nullable();
            $table->date('date_emission');
            $table->datetime('date_signature_producteur')->nullable();
            $table->datetime('date_enlevement')->nullable();
            $table->datetime('date_reception')->nullable();
            $table->datetime('date_traitement')->nullable();
            $table->datetime('date_refus')->nullable();
            $table->text('motif_refus')->nullable();
            $table->string('pdf_cerfa_path')->nullable();
            $table->foreignId('enlevement_id')->nullable()->constrained('enlevements')->nullOnDelete();
            $table->foreignId('ligne_enlevement_id')->nullable()->constrained('lignes_enlevement')->nullOnDelete();
            $table->datetime('derniere_sync')->nullable();
            $table->text('erreur_sync')->nullable();
            $table->foreignId('cree_par')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Add foreign key for bordereau_id on lignes_enlevement table
        Schema::table('lignes_enlevement', function (Blueprint $table) {
            $table->foreign('bordereau_id')->references('id')->on('bordereaux')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lignes_enlevement', function (Blueprint $table) {
            $table->dropForeign(['bordereau_id']);
        });

        Schema::dropIfExists('bordereaux');
    }
};
