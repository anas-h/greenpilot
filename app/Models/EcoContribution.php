<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'filiere',
        'eco_organisme',
        'annee',
        'trimestre',
        'quantite',
        'unite',
        'montant_contribution',
        'montant_collecte',
        'statut',
        'date_declaration',
        'date_paiement',
        'enlevement_ids',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'montant_contribution' => 'decimal:2',
            'montant_collecte' => 'decimal:2',
            'date_declaration' => 'date',
            'date_paiement' => 'date',
            'enlevement_ids' => 'array',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }
}
