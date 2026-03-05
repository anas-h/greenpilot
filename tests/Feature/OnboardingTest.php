<?php

namespace Tests\Feature;

use App\Models\Conteneur;
use App\Models\TypeDechet;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_complete_onboarding(): void
    {
        $types = TypeDechet::whereNull('entreprise_id')->take(3)->pluck('id')->toArray();

        $response = $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [
                'type_dechet_ids' => $types,
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('message', 'Configuration terminee.');
    }

    public function test_onboarding_creates_default_conteneurs(): void
    {
        $types = TypeDechet::whereNull('entreprise_id')->take(2)->pluck('id')->toArray();

        $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [
                'type_dechet_ids' => $types,
            ], $this->withGarageHeader());

        $this->assertEquals(2, Conteneur::where('garage_id', $this->garage->id)->count());
    }

    public function test_onboarding_activates_type_dechets_for_garage(): void
    {
        $types = TypeDechet::whereNull('entreprise_id')->take(3)->pluck('id')->toArray();

        $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [
                'type_dechet_ids' => $types,
            ], $this->withGarageHeader());

        $this->garage->refresh();
        $activatedIds = $this->garage->typesDechets->pluck('id')->toArray();

        foreach ($types as $typeId) {
            $this->assertContains($typeId, $activatedIds);
        }
    }

    public function test_onboarding_validates_type_dechet_ids(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_dechet_ids']);
    }

    public function test_onboarding_rejects_invalid_type_ids(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [
                'type_dechet_ids' => [99999],
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_dechet_ids.0']);
    }

    public function test_onboarding_requires_authentication(): void
    {
        $response = $this->postJson('/api/onboarding/complete', [
            'type_dechet_ids' => [1],
        ], $this->withGarageHeader());

        $response->assertUnauthorized();
    }

    public function test_onboarding_creates_conteneurs_with_correct_capacity(): void
    {
        $typeLitres = TypeDechet::whereNull('entreprise_id')
            ->where('unite_mesure', 'litres')
            ->first();

        $typeKg = TypeDechet::whereNull('entreprise_id')
            ->where('unite_mesure', 'kg')
            ->first();

        $types = collect([$typeLitres, $typeKg])->filter()->pluck('id')->toArray();

        if (count($types) < 2) {
            $this->markTestSkipped('Need at least one litres and one kg type');
        }

        $this->actingAsAdmin()
            ->postJson('/api/onboarding/complete', [
                'type_dechet_ids' => $types,
            ], $this->withGarageHeader());

        if ($typeLitres) {
            $conteneurLitres = Conteneur::where('garage_id', $this->garage->id)
                ->where('type_dechet_id', $typeLitres->id)
                ->first();
            $this->assertEquals(200, (int) $conteneurLitres->capacite);
        }

        if ($typeKg) {
            $conteneurKg = Conteneur::where('garage_id', $this->garage->id)
                ->where('type_dechet_id', $typeKg->id)
                ->first();
            $this->assertEquals(100, (int) $conteneurKg->capacite);
        }
    }
}
