<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enlevement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'numero',
        'collecteur_id',
        'date_prevue',
        'date_effective',
        'statut',
        'numero_bon_enlevement',
        'bon_enlevement_path',
        'cout_total',
        'revenu_total',
        'notes',
        'recurrence',
        'prochaine_date',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
            'date_effective' => 'datetime',
            'cout_total' => 'decimal:2',
            'revenu_total' => 'decimal:2',
            'prochaine_date' => 'date',
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

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneEnlevement::class);
    }

    public function bordereaux(): HasMany
    {
        return $this->hasMany(Bordereau::class);
    }
}
