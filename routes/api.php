<?php

use App\Http\Controllers\Api\Admin\AdminEntrepriseController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AlerteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BordereauController;
use App\Http\Controllers\Api\CollecteurController;
use App\Http\Controllers\Api\ConfigTrackdechetsController;
use App\Http\Controllers\Api\ConteneurController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EcoContributionController;
use App\Http\Controllers\Api\EnlevementController;
use App\Http\Controllers\Api\FicheSecuriteController;
use App\Http\Controllers\Api\GarageController;
use App\Http\Controllers\Api\MappingOperationController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PneuParticulierController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\RegistreController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TypeDechetController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public (unauthenticated) ─────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// ─── Stripe Webhook (no auth, verified by signature) ──────────────
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// ─── Authenticated ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'entreprise.active'])->group(function () {

    // Auth & Profil (read access always allowed)
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Subscription management (always accessible - even in read-only)
    Route::get('/subscription', [SubscriptionController::class, 'status']);
    Route::get('/subscription/config', [SubscriptionController::class, 'config']);
    Route::post('/subscription/create', [SubscriptionController::class, 'createSubscription']);
    Route::post('/subscription/confirm', [SubscriptionController::class, 'confirmSubscription']);
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
    Route::post('/subscription/portal', [SubscriptionController::class, 'portal']);
    Route::post('/subscription/preview-change', [SubscriptionController::class, 'previewChange']);
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::get('/subscription/invoices', [SubscriptionController::class, 'invoices']);
    Route::get('/subscription/invoices/{invoiceId}/pdf', [SubscriptionController::class, 'downloadInvoice']);
    Route::get('/subscription/payment-methods', [SubscriptionController::class, 'paymentMethods']);

    // System types (no garage needed, read-only)
    Route::get('/types-dechets/systeme', [TypeDechetController::class, 'systeme']);

    // ─── Write-protected routes ─────────────────────────────────
    Route::middleware('subscription.write')->group(function () {

        // Garages (no garage middleware - need to list/create without one)
        Route::apiResource('garages', GarageController::class);

        // Users (admin only)
        Route::apiResource('users', UserController::class);

        // Onboarding
        Route::post('/onboarding/complete', [OnboardingController::class, 'complete']);

        // ─── Garage-scoped routes ─────────────────────────────────
        Route::middleware('garage')->group(function () {

            // Dashboard
            Route::get('/dashboard', [DashboardController::class, 'index']);
            Route::get('/dashboard/economie', [DashboardController::class, 'economie']);

            // Types de dechets
            Route::apiResource('types-dechets', TypeDechetController::class);
            Route::post('/types-dechets/{typeDechet}/toggle', [TypeDechetController::class, 'toggleActivation']);

            // Conteneurs
            Route::apiResource('conteneurs', ConteneurController::class);
            Route::get('/conteneurs/scan/{codeQr}', [ConteneurController::class, 'scan']);
            Route::get('/conteneurs/{conteneur}/historique', [ConteneurController::class, 'historique']);
            Route::get('/conteneurs/{conteneur}/qr-download', [ConteneurController::class, 'downloadQr']);
            Route::get('/conteneurs-conformite', [ConteneurController::class, 'conformite']);

            // Collecteurs
            Route::apiResource('collecteurs', CollecteurController::class);
            Route::get('/collecteurs-comparatif', [CollecteurController::class, 'comparatif']);

            // Productions
            Route::apiResource('productions', ProductionController::class);
            Route::post('/productions/batch', [ProductionController::class, 'storeBatch']);
            Route::post('/productions/validate', [ProductionController::class, 'validateProductions']);
            Route::post('/productions/suggestions', [ProductionController::class, 'suggestions']);

            // Enlevements
            Route::get('/enlevements/calendrier', [EnlevementController::class, 'calendrier']);
            Route::apiResource('enlevements', EnlevementController::class);
            Route::post('/enlevements/{enlevement}/complete', [EnlevementController::class, 'complete']);
            Route::get('/enlevements/{enlevement}/bon-pdf', [EnlevementController::class, 'bonPdf']);

            // Bordereaux (BSD)
            Route::apiResource('bordereaux', BordereauController::class);
            Route::post('/bordereaux/{bordereau}/publish', [BordereauController::class, 'publish']);
            Route::post('/bordereaux/{bordereau}/sign', [BordereauController::class, 'sign']);
            Route::post('/bordereaux/{bordereau}/cancel', [BordereauController::class, 'cancel']);
            Route::post('/bordereaux/{bordereau}/dupliquer', [BordereauController::class, 'dupliquer']);
            Route::get('/bordereaux/{bordereau}/pdf', [BordereauController::class, 'pdf']);

            // Registre
            Route::get('/registre', [RegistreController::class, 'index']);
            Route::get('/registre/export', [RegistreController::class, 'export']);

            // Alertes
            Route::get('/alertes', [AlerteController::class, 'index']);
            Route::get('/alertes/count', [AlerteController::class, 'count']);
            Route::post('/alertes/mark-read', [AlerteController::class, 'markRead']);
            Route::post('/alertes/{alerte}/resolve', [AlerteController::class, 'resolve']);

            // Fiches de securite
            Route::apiResource('fiches-securite', FicheSecuriteController::class);

            // Conformite
            Route::get('/conformite/score', [App\Http\Controllers\Api\ConformiteController::class, 'score']);

            // Eco-contributions
            Route::apiResource('eco-contributions', EcoContributionController::class);
            Route::post('/eco-contributions/{ecoContribution}/declare', [EcoContributionController::class, 'declare']);
            Route::post('/eco-contributions/{ecoContribution}/payer', [EcoContributionController::class, 'payer']);

            // Pneus particuliers
            Route::apiResource('pneus-particuliers', PneuParticulierController::class)->only(['index', 'store']);
            Route::get('/pneus-particuliers/compteur', [PneuParticulierController::class, 'compteurAnnuel']);

            // Mappings
            Route::apiResource('mappings', MappingOperationController::class);
            Route::post('/mappings/suggest', [MappingOperationController::class, 'suggest']);

            // Config Trackdechets
            Route::get('/config-trackdechets', [ConfigTrackdechetsController::class, 'show']);
            Route::post('/config-trackdechets', [ConfigTrackdechetsController::class, 'store']);
            Route::post('/config-trackdechets/test', [ConfigTrackdechetsController::class, 'test']);
            Route::get('/trackdechets/search-companies', [ConfigTrackdechetsController::class, 'searchCompanies']);
        });
    });
});

// ─── Admin (super_admin only) ─────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('entreprises', AdminEntrepriseController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('entreprises/{id}/toggle-active', [AdminEntrepriseController::class, 'toggleActive']);
    Route::post('entreprises/{id}/toggle-archive', [AdminEntrepriseController::class, 'toggleArchive']);
    Route::post('entreprises/{id}/garages', [AdminEntrepriseController::class, 'storeGarage']);
    Route::post('entreprises/{id}/users', [AdminEntrepriseController::class, 'storeUser']);
    Route::get('stats', [AdminEntrepriseController::class, 'stats']);
    Route::get('users', [AdminUserController::class, 'index']);
    Route::put('users/{id}', [AdminUserController::class, 'update']);
    Route::delete('users/{id}', [AdminUserController::class, 'destroy']);
    Route::post('users/{id}/login-as', [AdminUserController::class, 'loginAs']);
    Route::put('garages/{id}', [AdminEntrepriseController::class, 'updateGarage']);
    Route::delete('garages/{id}', [AdminEntrepriseController::class, 'destroyGarage']);
    Route::get('entreprises/{id}/config-trackdechets', [AdminEntrepriseController::class, 'getTrackdechetsConfig']);
    Route::post('entreprises/{id}/config-trackdechets', [AdminEntrepriseController::class, 'saveTrackdechetsConfig']);
    Route::post('entreprises/{id}/config-trackdechets/test', [AdminEntrepriseController::class, 'testTrackdechetsConfig']);
    Route::get('bordereaux', [AdminEntrepriseController::class, 'bordereaux']);
    Route::post('bordereaux/{id}/retry', [AdminEntrepriseController::class, 'retryBsd']);
});
