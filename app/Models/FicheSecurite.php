<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FicheSecurite extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fiches_securite';

    protected $fillable = [
        'garage_id',
        'type_dechet_id',
        'nom_produit',
        'fournisseur',
        'fichier_path',
        'date_emission',
        'date_validite',
        'actif',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_validite' => 'date',
            'actif' => 'boolean',
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

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }
}
