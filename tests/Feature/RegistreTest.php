<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
    }

    public function test_can_view_registre(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/registre', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_export_registre_csv(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/registre/export?format=csv', $this->withGarageHeader());

        $response->assertOk();
    }

    public function test_registre_filters_by_date(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/registre?date_debut=2024-01-01&date_fin=2024-12-31', $this->withGarageHeader());

        $response->assertOk();
    }
}
