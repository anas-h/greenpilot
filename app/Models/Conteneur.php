<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conteneur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'type_dechet_id',
        'nom',
        'code_qr',
        'capacite',
        'unite',
        'niveau_actuel',
        'emplacement',
        'mixte',
        'date_dernier_enlevement',
        'date_mise_en_service',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'capacite' => 'decimal:2',
            'niveau_actuel' => 'decimal:2',
            'mixte' => 'boolean',
            'actif' => 'boolean',
            'date_dernier_enlevement' => 'date',
            'date_mise_en_service' => 'date',
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

    public function historiques(): HasMany
    {
        return $this->hasMany(HistoriqueConteneur::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    /**
     * Get the fill percentage of the container.
     */
    public function getPourcentageRemplissage(): float
    {
        if ($this->capacite == 0) {
            return 0;
        }

        return ($this->niveau_actuel / $this->capacite) * 100;
    }
}
