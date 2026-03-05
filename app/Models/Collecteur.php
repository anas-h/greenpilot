<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collecteur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'garage_id',
        'raison_sociale',
        'siret',
        'adresse',
        'code_postal',
        'ville',
        'numero_autorisation',
        'date_validite_autorisation',
        'autorisation_adr',
        'numero_adr',
        'eco_organisme',
        'contact_nom',
        'contact_telephone',
        'contact_email',
        'site_web',
        'notes',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'date_validite_autorisation' => 'date',
            'autorisation_adr' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function typesDechets(): BelongsToMany
    {
        return $this->belongsToMany(TypeDechet::class, 'collecteur_type_dechet')
            ->withPivot('prix_unitaire', 'prix_forfaitaire', 'est_rachat', 'date_effet', 'notes')
            ->withTimestamps();
    }

    public function enlevements(): HasMany
    {
        return $this->hasMany(Enlevement::class);
    }

    /**
     * Check if the collector's authorization is still valid.
     */
    public function isAutorisationValide(): bool
    {
        if (is_null($this->date_validite_autorisation)) {
            return false;
        }

        return $this->date_validite_autorisation->isFuture() || $this->date_validite_autorisation->isToday();
    }
}
