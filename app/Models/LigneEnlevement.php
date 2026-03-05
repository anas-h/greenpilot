<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneEnlevement extends Model
{
    use HasFactory;

    protected $table = 'lignes_enlevement';

    protected $fillable = [
        'enlevement_id',
        'type_dechet_id',
        'conteneur_id',
        'quantite_prevue',
        'quantite_effective',
        'unite',
        'prix_unitaire',
        'est_rachat',
        'montant',
        'bordereau_id',
    ];

    protected function casts(): array
    {
        return [
            'quantite_prevue' => 'decimal:3',
            'quantite_effective' => 'decimal:3',
            'prix_unitaire' => 'decimal:2',
            'est_rachat' => 'boolean',
            'montant' => 'decimal:2',
        ];
    }

    public function enlevement(): BelongsTo
    {
        return $this->belongsTo(Enlevement::class);
    }

    public function typeDechet(): BelongsTo
    {
        return $this->belongsTo(TypeDechet::class);
    }

    public function conteneur(): BelongsTo
    {
        return $this->belongsTo(Conteneur::class);
    }

    public function bordereau(): BelongsTo
    {
        return $this->belongsTo(Bordereau::class);
    }
}
