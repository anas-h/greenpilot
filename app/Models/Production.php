<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'type_dechet_id',
        'conteneur_id',
        'quantite',
        'unite',
        'date_production',
        'source_type',
        'reference_externe',
        'vehicule_info',
        'employe_id',
        'valide',
        'valide_par',
        'date_validation',
        'correction_de',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:3',
            'date_production' => 'date',
            'valide' => 'boolean',
            'date_validation' => 'datetime',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function typeDechet(): BelongsTo
    {
        return $this->belongsTo(TypeDechet::class);
    }

    public function conteneur(): BelongsTo
    {
        return $this->belongsTo(Conteneur::class);
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employe_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function correctionDe(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_de');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'correction_de');
    }
}
