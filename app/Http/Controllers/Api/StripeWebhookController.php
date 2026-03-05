<?php

namespace App\Http\Controllers\Api;

use App\Models\Entreprise;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    public function handleCustomerSubscriptionCreated(array $payload): void
    {
        $this->updateEntreprisePlan($payload);
    }

    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $this->updateEntreprisePlan($payload);
    }

    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $entreprise = Entreprise::where('stripe_id', $stripeId)->first();

        if ($entreprise) {
            Log::info("Subscription deleted for entreprise {$entreprise->id}");
        }
    }

    public function handleInvoicePaymentFailed(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $entreprise = Entreprise::where('stripe_id', $stripeId)->first();

        if ($entreprise) {
            Log::warning("Payment failed for entreprise {$entreprise->id} ({$entreprise->raison_sociale})");
        }
    }

    private function updateEntreprisePlan(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $entreprise = Entreprise::where('stripe_id', $stripeId)->first();

        if (! $entreprise) {
            return;
        }

        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;

        if (! $priceId) {
            return;
        }

        $premiumPriceId = config('services.stripe.premium_price_id');

        $plan = ($priceId === $premiumPriceId) ? 'premium' : 'standard';

        $limits = match ($plan) {
            'premium' => ['max_garages' => 999, 'max_users' => 999],
            default => ['max_garages' => 3, 'max_users' => 15],
        };

        $entreprise->update(array_merge(['plan' => $plan], $limits));

        Log::info("Entreprise {$entreprise->id} plan updated to {$plan}");
    }
}
