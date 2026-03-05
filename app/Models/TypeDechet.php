<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeDechet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'types_dechets';

    protected $fillable = [
        'entreprise_id',
        'code_europeen',
        'denomination',
        'dangereux',
        'unite_mesure',
        'categorie',
        'filiere_rep',
        'pictogrammes_danger',
        'consignes_stockage',
        'consignes_tri',
    ];

    protected function casts(): array
    {
        return [
            'dangereux' => 'boolean',
            'pictogrammes_danger' => 'array',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function garages(): BelongsToMany
    {
        return $this->belongsToMany(Garage::class, 'garage_type_dechet')
            ->withPivot('actif', 'quantite_defaut');
    }

    public function conteneurs(): HasMany
    {
        return $this->hasMany(Conteneur::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
}
