<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alerte extends Model
{
    use HasFactory;

    protected $fillable = [
        'garage_id',
        'type',
        'priorite',
        'titre',
        'message',
        'detail',
        'entite_type',
        'entite_id',
        'lue',
        'resolue',
        'resolue_par',
        'date_resolution',
    ];

    protected function casts(): array
    {
        return [
            'lue' => 'boolean',
            'resolue' => 'boolean',
            'date_resolution' => 'datetime',
        ];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function resoluePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolue_par');
    }

    public function entite(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeNonLues(Builder $query): Builder
    {
        return $query->where('lue', false);
    }

    public function scopeNonResolues(Builder $query): Builder
    {
        return $query->where('resolue', false);
    }

    public function scopeCritiques(Builder $query): Builder
    {
        return $query->where('priorite', 'critique');
    }
}
