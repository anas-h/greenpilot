<?php

use App\Models\Entreprise;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Support\Facades\Schedule;

// Check alertes every day at 8:00 AM
Schedule::command('dechets:check-alertes')->dailyAt('08:00')->timezone('Europe/Paris');

// Sync BSD with Trackdechets every 30 minutes
Schedule::command('dechets:sync-bsd')->everyThirtyMinutes();

// Check trial expiration: notify 3 days before and on expiration day
Schedule::call(function () {
    // 3 days before expiration
    $expiringIn3Days = Entreprise::where('actif', true)
        ->whereDate('trial_ends_at', now()->addDays(3)->toDateString())
        ->get();

    foreach ($expiringIn3Days as $entreprise) {
        if (! $entreprise->hasActiveSubscription()) {
            $admin = $entreprise->users()->where('role', 'admin')->first();
            $admin?->notify(new TrialExpiringNotification(3));
        }
    }

    // Expiring today
    $expiringToday = Entreprise::where('actif', true)
        ->whereDate('trial_ends_at', now()->toDateString())
        ->get();

    foreach ($expiringToday as $entreprise) {
        if (! $entreprise->hasActiveSubscription()) {
            $admin = $entreprise->users()->where('role', 'admin')->first();
            $admin?->notify(new TrialExpiringNotification(0));
        }
    }
})->dailyAt('09:00')->timezone('Europe/Paris');
