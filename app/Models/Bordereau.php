<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bordereau extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bordereaux';

    protected $fillable = [
        'garage_id',
        'type_bsd',
        'trackdechets_id',
        'trackdechets_numero',
        'statut',
        'statut_sync',
        'code_dechet',
        'denomination_dechet',
        'quantite',
        'unite',
        'collecteur_id',
        'transporteur_siret',
        'transporteur_raison_sociale',
        'destination_siret',
        'destination_raison_sociale',
        'destination_adresse',
        'code_traitement',
        'cap',
        'date_emission',
        'date_signature_producteur',
        'date_enlevement',
        'date_reception',
        'date_traitement',
        'date_refus',
        'motif_refus',
        'pdf_cerfa_path',
        'enlevement_id',
        'ligne_enlevement_id',
        'derniere_sync',
        'erreur_sync',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'date_emission' => 'date',
            'date_signature_producteur' => 'datetime',
            'date_enlevement' => 'datetime',
            'date_reception' => 'datetime',
            'date_traitement' => 'datetime',
            'date_refus' => 'datetime',
            'derniere_sync' => 'datetime',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function collecteur(): BelongsTo
    {
        return $this->belongsTo(Collecteur::class);
    }

    public function enlevement(): BelongsTo
    {
        return $this->belongsTo(Enlevement::class);
    }

    public function ligneEnlevement(): BelongsTo
    {
        return $this->belongsTo(LigneEnlevement::class);
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }
}
