<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriseChargePneu extends Model
{
    use HasFactory;

    protected $table = 'prises_charge_pneus';

    protected $fillable = [
        'garage_id',
        'nom_particulier',
        'telephone',
        'quantite',
        'date',
        'notes',
        'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }
}
