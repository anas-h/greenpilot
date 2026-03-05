<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'entreprise_id',
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'role',
        'actif',
        'preferences_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'preferences_notifications' => 'array',
            'actif' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function garages(): BelongsToMany
    {
        return $this->belongsToMany(Garage::class, 'user_garage')->withTimestamps();
    }

    /**
     * Check if the user has access to a specific garage.
     */
    public function hasAccessToGarage(int $garageId): bool
    {
        if (in_array($this->role, ['admin', 'super_admin'])) {
            return true;
        }

        return $this->garages()->where('garages.id', $garageId)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
