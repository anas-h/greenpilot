<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Entreprise extends Model
{
    use Billable, HasFactory, SoftDeletes;

    protected $fillable = [
        'raison_sociale',
        'siret',
        'forme_juridique',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'email_contact',
        'logo_path',
        'plan',
        'trial_ends_at',
        'actif',
        'archived_at',
        'max_garages',
        'max_users',
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'actif' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture()
            && ! $this->hasActiveSubscription();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default');
    }

    public function isReadOnly(): bool
    {
        return ! $this->isOnTrial() && ! $this->hasActiveSubscription();
    }

    public function trialDaysRemaining(): int
    {
        if ($this->trial_ends_at === null || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    public function isActive(): bool
    {
        return $this->actif && ($this->isOnTrial() || $this->hasActiveSubscription() || $this->isReadOnly());
    }

    public function canAddGarage(): bool
    {
        return $this->garages()->count() < $this->max_garages;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function garages(): HasMany
    {
        return $this->hasMany(Garage::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function typesDechets(): HasMany
    {
        return $this->hasMany(TypeDechet::class);
    }

    public function configTrackdechets(): HasOne
    {
        return $this->hasOne(ConfigTrackdechets::class);
    }
}
