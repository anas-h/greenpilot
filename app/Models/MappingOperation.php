<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MappingOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'mot_cle',
        'type_dechet_id',
        'quantite_defaut',
        'unite',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'quantite_defaut' => 'decimal:3',
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
}
