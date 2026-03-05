<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HistoriqueConteneur extends Model
{
    use HasFactory;

    protected $table = 'historique_conteneurs';

    protected $fillable = [
        'conteneur_id',
        'type_evenement',
        'quantite',
        'niveau_avant',
        'niveau_apres',
        'reference_type',
        'reference_id',
        'user_id',
        'date_evenement',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'niveau_avant' => 'decimal:2',
            'niveau_apres' => 'decimal:2',
            'date_evenement' => 'datetime',
        ];
    }

    public function conteneur(): BelongsTo
    {
        return $this->belongsTo(Conteneur::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
