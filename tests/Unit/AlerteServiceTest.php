<?php

namespace Tests\Unit;

use App\Models\Collecteur;
use App\Models\Conteneur;
use App\Models\TypeDechet;
use App\Services\AlerteService;
use Tests\TestCase;

class AlerteServiceTest extends TestCase
{
    private AlerteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->service = new AlerteService;
    }

    public function test_detects_conteneur_at_80_percent(): void
    {
        $type = TypeDechet::where('dangereux', true)->first();
        Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'nom' => 'Fut plein',
            'code_qr' => 'ECO-ALERT-80',
            'capacite' => 100,
            'unite' => 'litres',
            'niveau_actuel' => 85,
            'actif' => true,
        ]);

        $alertes = $this->service->checkAllForGarage($this->garage->id);

        $containsAlert = collect($alertes)->contains(fn ($a) => $a->type === 'conteneur_80' || $a->type === 'conteneur_95'
        );
        $this->assertTrue($containsAlert);
    }

    public function test_detects_expired_authorization(): void
    {
        Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Expire SARL',
            'siret' => '44444444444444',
            'numero_autorisation' => 'AUTH-EXP',
            'date_validite_autorisation' => now()->subDay(),
            'actif' => true,
        ]);

        $alertes = $this->service->checkAllForGarage($this->garage->id);

        $containsAlert = collect($alertes)->contains(fn ($a) => $a->type === 'autorisation_expiree'
        );
        $this->assertTrue($containsAlert);
    }

    public function test_does_not_duplicate_alertes(): void
    {
        Collecteur::create([
            'garage_id' => $this->garage->id,
            'raison_sociale' => 'Expire Bis',
            'siret' => '55555555555555',
            'numero_autorisation' => 'AUTH-DUP',
            'date_validite_autorisation' => now()->subDay(),
            'actif' => true,
        ]);

        // Run twice
        $this->service->checkAllForGarage($this->garage->id);
        $alertes2 = $this->service->checkAllForGarage($this->garage->id);

        // Second run should not create duplicates for the same collecteur
        $authExpired = collect($alertes2)->filter(fn ($a) => $a->type === 'autorisation_expiree');
        $this->assertLessThanOrEqual(1, $authExpired->count());
    }

    public function test_detects_stockage_10_months(): void
    {
        $type = TypeDechet::where('dangereux', true)->first();
        Conteneur::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $type->id,
            'nom' => 'Vieux fut',
            'code_qr' => 'ECO-OLD-10M',
            'capacite' => 100,
            'unite' => 'litres',
            'niveau_actuel' => 50,
            'date_mise_en_service' => now()->subMonths(11),
            'date_dernier_enlevement' => now()->subMonths(11),
            'actif' => true,
        ]);

        $alertes = $this->service->checkAllForGarage($this->garage->id);

        $containsAlert = collect($alertes)->contains(fn ($a) => str_starts_with($a->type, 'stockage_')
        );
        $this->assertTrue($containsAlert);
    }
}
