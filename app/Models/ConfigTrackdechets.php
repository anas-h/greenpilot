<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigTrackdechets extends Model
{
    use HasFactory;

    protected $table = 'config_trackdechets';

    protected $fillable = [
        'entreprise_id',
        'api_token',
        'environnement',
        'actif',
        'derniere_sync_reussie',
        'derniere_erreur',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'derniere_sync_reussie' => 'datetime',
            'api_token' => 'encrypted',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }
}
