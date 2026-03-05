<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entreprise_id',
        'nom',
        'siret_etablissement',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'surface_atelier',
        'regime_icpe',
        'activites',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'activites' => 'array',
            'actif' => 'boolean',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_garage')->withTimestamps();
    }

    public function conteneurs(): HasMany
    {
        return $this->hasMany(Conteneur::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function enlevements(): HasMany
    {
        return $this->hasMany(Enlevement::class);
    }

    public function bordereaux(): HasMany
    {
        return $this->hasMany(Bordereau::class);
    }

    public function collecteurs(): HasMany
    {
        return $this->hasMany(Collecteur::class);
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(Alerte::class);
    }

    public function ecoContributions(): HasMany
    {
        return $this->hasMany(EcoContribution::class);
    }

    public function prisesChargePneus(): HasMany
    {
        return $this->hasMany(PriseChargePneu::class);
    }

    public function fichesSecurite(): HasMany
    {
        return $this->hasMany(FicheSecurite::class);
    }

    public function mappingOperations(): HasMany
    {
        return $this->hasMany(MappingOperation::class);
    }

    public function typesDechets(): BelongsToMany
    {
        return $this->belongsToMany(TypeDechet::class, 'garage_type_dechet')
            ->withPivot('actif', 'quantite_defaut');
    }
}
