<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubscriptionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $entreprise = $request->user()->entreprise;

        $data = [
            'plan' => $entreprise->plan,
            'trial_ends_at' => $entreprise->trial_ends_at,
            'on_trial' => $entreprise->isOnTrial(),
            'days_remaining' => $entreprise->trialDaysRemaining(),
            'subscribed' => $entreprise->hasActiveSubscription(),
            'read_only' => $entreprise->isReadOnly(),
            'stripe_id' => $entreprise->stripe_id,
            'pm_type' => $entreprise->pm_type,
            'pm_last_four' => $entreprise->pm_last_four,
        ];

        // Add subscription details if actively subscribed
        $subscription = $entreprise->subscription('default');
        if ($subscription && $subscription->valid()) {
            $data['subscription'] = [
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
            ];

            $periodStart = $subscription->currentPeriodStart();
            $periodEnd = $subscription->currentPeriodEnd();
            if ($periodStart) {
                $data['subscription']['current_period_start'] = $periodStart->toIso8601String();
            }
            if ($periodEnd) {
                $data['subscription']['current_period_end'] = $periodEnd->toIso8601String();
            }
        }

        return response()->json($data);
    }

    /**
     * Return Stripe public key for frontend Elements.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'public_key' => config('cashier.key'),
        ]);
    }

    /**
     * Create an incomplete Stripe subscription and return
     * the PaymentIntent client_secret for the Payment Element.
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:standard,premium',
        ]);

        $entreprise = $request->user()->entreprise;

        if ($entreprise->hasActiveSubscription()) {
            return response()->json([
                'message' => 'Vous avez deja un abonnement actif.',
            ], 422);
        }

        $priceId = $request->plan === 'premium'
            ? config('services.stripe.premium_price_id')
            : config('services.stripe.standard_price_id');

        // Ensure the entreprise is a Stripe customer
        if (! $entreprise->stripe_id) {
            $entreprise->createAsStripeCustomer([
                'email' => $request->user()->email,
                'name' => $entreprise->raison_sociale,
            ]);
        }

        Stripe::setApiKey(config('cashier.secret'));

        try {
            // Create Stripe subscription with incomplete payment
            // Expand both payment_intent and pending_setup_intent to handle all cases
            $stripeSubscription = StripeSubscription::create([
                'customer' => $entreprise->stripe_id,
                'items' => [['price' => $priceId]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
                'expand' => ['latest_invoice.payment_intent', 'pending_setup_intent'],
            ]);

            // Stripe may return a PaymentIntent (non-zero invoice) or a SetupIntent
            // ($0 invoice / trial / certain payment_settings configurations)
            $paymentIntent = $stripeSubscription->latest_invoice->payment_intent ?? null;
            $setupIntent = $stripeSubscription->pending_setup_intent ?? null;

            $clientSecret = $paymentIntent?->client_secret ?? $setupIntent?->client_secret ?? null;
            $type = $paymentIntent ? 'payment_intent' : 'setup_intent';

            if (! $clientSecret) {
                $stripeSubscription->cancel();

                return response()->json([
                    'message' => 'Impossible de creer le paiement. Veuillez contacter le support.',
                ], 500);
            }

            return response()->json([
                'subscription_id' => $stripeSubscription->id,
                'client_secret' => $clientSecret,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur Stripe: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Called after payment is confirmed on the frontend.
     * Syncs the Stripe subscription with the local Cashier model.
     */
    public function confirmSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|string',
            'plan' => 'required|in:standard,premium',
        ]);

        $entreprise = $request->user()->entreprise;

        Stripe::setApiKey(config('cashier.secret'));

        // Retrieve the subscription from Stripe to verify it's active
        $stripeSubscription = StripeSubscription::retrieve($request->subscription_id);

        if (! in_array($stripeSubscription->status, ['active', 'trialing'])) {
            return response()->json([
                'message' => 'Le paiement n\'a pas encore ete confirme.',
            ], 422);
        }

        // Create the local Cashier subscription record if it doesn't exist
        $localSubscription = $entreprise->subscriptions()->where('stripe_id', $stripeSubscription->id)->first();

        if (! $localSubscription) {
            $entreprise->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => $stripeSubscription->id,
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? null,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);
        } else {
            $localSubscription->update([
                'stripe_status' => $stripeSubscription->status,
            ]);
        }

        // Update local plan and limits
        $limits = $this->planLimits($request->plan);
        $entreprise->update(array_merge(['plan' => $request->plan], $limits));

        return response()->json([
            'message' => 'Abonnement active avec succes.',
            'plan' => $request->plan,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:standard,premium',
        ]);

        $entreprise = $request->user()->entreprise;
        $plan = $request->plan;

        $priceId = $plan === 'premium'
            ? config('services.stripe.premium_price_id')
            : config('services.stripe.standard_price_id');

        $checkout = $entreprise->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => config('app.url').'/abonnement?success=1',
                'cancel_url' => config('app.url').'/abonnement?canceled=1',
            ]);

        return response()->json([
            'checkout_url' => $checkout->url,
        ]);
    }

    public function portal(Request $request): JsonResponse
    {
        $entreprise = $request->user()->entreprise;

        $url = $entreprise->billingPortalUrl(
            config('app.url').'/abonnement'
        );

        return response()->json([
            'portal_url' => $url,
        ]);
    }

    public function previewChange(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:standard,premium',
        ]);

        $entreprise = $request->user()->entreprise;
        $plan = $request->plan;

        if ($plan === $entreprise->plan) {
            return response()->json([
                'message' => 'Vous etes deja sur ce plan.',
            ], 422);
        }

        $direction = $plan === 'premium' ? 'upgrade' : 'downgrade';
        $newLimits = $this->planLimits($plan);

        // Check limits for downgrade
        if ($direction === 'downgrade') {
            $currentGarages = $entreprise->garages()->count();
            $currentUsers = $entreprise->users()->count();

            if ($currentGarages > $newLimits['max_garages'] || $currentUsers > $newLimits['max_users']) {
                return response()->json([
                    'message' => 'Votre utilisation actuelle depasse les limites du plan Standard.',
                    'limits_exceeded' => true,
                    'current_garages' => $currentGarages,
                    'max_garages' => $newLimits['max_garages'],
                    'current_users' => $currentUsers,
                    'max_users' => $newLimits['max_users'],
                ], 422);
            }
        }

        $priceId = $plan === 'premium'
            ? config('services.stripe.premium_price_id')
            : config('services.stripe.standard_price_id');

        $subscription = $entreprise->subscription('default');

        try {
            $preview = $subscription->previewInvoice($priceId);
            $stripeInvoice = $preview->asStripeInvoice();

            $credit = 0;
            $charge = 0;
            foreach ($stripeInvoice->lines->data as $line) {
                if ($line->amount < 0) {
                    $credit += $line->amount;
                } else {
                    $charge += $line->amount;
                }
            }
            $total = $stripeInvoice->total;

            $formatAmount = fn ($cents) => number_format($cents / 100, 2, ',', ' ').' €';

            $periodEnd = $subscription->currentPeriodEnd();
            $nextBillingDate = $periodEnd?->toIso8601String();

            return response()->json([
                'can_change' => true,
                'direction' => $direction,
                'new_plan' => $plan,
                'new_price' => $plan === 'premium' ? '99 €/mois' : '49 €/mois',
                'next_billing_date' => $nextBillingDate,
                'proration' => [
                    'credit' => $formatAmount($credit),
                    'charge' => $formatAmount($charge),
                    'total' => $formatAmount($total),
                ],
                'new_limits' => $newLimits,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Impossible de previsualiser le changement: '.$e->getMessage(),
            ], 500);
        }
    }

    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:standard,premium',
        ]);

        $entreprise = $request->user()->entreprise;
        $plan = $request->plan;

        if ($plan === $entreprise->plan) {
            return response()->json([
                'message' => 'Vous etes deja sur ce plan.',
            ], 422);
        }

        // Verify limits for downgrade (server-side safety check)
        if ($plan === 'standard') {
            $newLimits = $this->planLimits($plan);
            $currentGarages = $entreprise->garages()->count();
            $currentUsers = $entreprise->users()->count();

            if ($currentGarages > $newLimits['max_garages'] || $currentUsers > $newLimits['max_users']) {
                return response()->json([
                    'message' => 'Votre utilisation actuelle depasse les limites du plan Standard.',
                    'limits_exceeded' => true,
                    'current_garages' => $currentGarages,
                    'max_garages' => $newLimits['max_garages'],
                    'current_users' => $currentUsers,
                    'max_users' => $newLimits['max_users'],
                ], 422);
            }
        }

        $priceId = $plan === 'premium'
            ? config('services.stripe.premium_price_id')
            : config('services.stripe.standard_price_id');

        $entreprise->subscription('default')->swap($priceId);

        $limits = $this->planLimits($plan);
        $entreprise->update(array_merge(['plan' => $plan], $limits));

        return response()->json([
            'message' => 'Plan mis a jour.',
            'plan' => $plan,
        ]);
    }

    /**
     * List invoices for the current entreprise.
     */
    public function invoices(Request $request): JsonResponse
    {
        $entreprise = $request->user()->entreprise;

        if (! $entreprise->stripe_id) {
            return response()->json([]);
        }

        try {
            $invoices = $entreprise->invoices();

            return response()->json($invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toIso8601String(),
                    'total' => $invoice->total(),
                    'status' => $invoice->status,
                    'number' => $invoice->number,
                    'invoice_pdf' => $invoice->invoicePdf,
                    'hosted_invoice_url' => $invoice->hostedInvoiceUrl,
                ];
            })->values());
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Download an invoice as PDF.
     */
    public function downloadInvoice(Request $request, string $invoiceId): SymfonyResponse
    {
        $entreprise = $request->user()->entreprise;

        return $entreprise->downloadInvoice($invoiceId, [
            'vendor' => 'GreenPilot — EcoGarage',
            'product' => 'Abonnement '.ucfirst($entreprise->plan ?? 'Standard'),
        ]);
    }

    /**
     * List payment methods for the current entreprise.
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $entreprise = $request->user()->entreprise;

        if (! $entreprise->stripe_id) {
            return response()->json([]);
        }

        try {
            $methods = $entreprise->paymentMethods();
            $defaultMethod = $entreprise->defaultPaymentMethod();
            $defaultId = $defaultMethod?->id;

            return response()->json($methods->map(function ($pm) use ($defaultId) {
                return [
                    'id' => $pm->id,
                    'type' => $pm->type,
                    'brand' => $pm->card?->brand ?? null,
                    'last4' => $pm->card?->last4 ?? null,
                    'exp_month' => $pm->card?->exp_month ?? null,
                    'exp_year' => $pm->card?->exp_year ?? null,
                    'is_default' => $pm->id === $defaultId,
                ];
            })->values());
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    private function planLimits(string $plan): array
    {
        return match ($plan) {
            'premium' => ['max_garages' => 999, 'max_users' => 999],
            default => ['max_garages' => 1, 'max_users' => 5],
        };
    }
}
