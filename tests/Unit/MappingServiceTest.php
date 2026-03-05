<?php

namespace Tests\Unit;

use App\Models\MappingOperation;
use App\Models\TypeDechet;
use App\Services\MappingService;
use Tests\TestCase;

class MappingServiceTest extends TestCase
{
    private MappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();
        $this->service = new MappingService;
    }

    public function test_finds_suggestion_for_vidange(): void
    {
        $type = TypeDechet::where('code_europeen', '13 02 05*')->first();
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange moteur',
            'type_dechet_id' => $type->id,
            'quantite_defaut' => 5,
            'unite' => 'litres',
            'actif' => true,
        ]);

        $suggestions = $this->service->findSuggestions($this->garage->id, 'Vidange moteur 5W30');

        $this->assertCount(1, $suggestions);
        $this->assertEquals('vidange moteur', $suggestions[0]['mot_cle']);
        $this->assertEquals(5, $suggestions[0]['quantite_defaut']);
    }

    public function test_finds_multiple_suggestions(): void
    {
        $huile = TypeDechet::where('code_europeen', '13 02 05*')->first();
        $filtre = TypeDechet::where('code_europeen', '16 01 07*')->first();

        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $huile->id,
            'quantite_defaut' => 5,
            'unite' => 'litres',
            'actif' => true,
        ]);
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'filtre',
            'type_dechet_id' => $filtre->id,
            'quantite_defaut' => 0.5,
            'unite' => 'kg',
            'actif' => true,
        ]);

        $suggestions = $this->service->findSuggestions($this->garage->id, 'Vidange + filtre huile');

        $this->assertCount(2, $suggestions);
    }

    public function test_ignores_inactive_mappings(): void
    {
        $type = TypeDechet::where('code_europeen', '13 02 05*')->first();
        MappingOperation::create([
            'garage_id' => $this->garage->id,
            'mot_cle' => 'vidange',
            'type_dechet_id' => $type->id,
            'quantite_defaut' => 5,
            'unite' => 'litres',
            'actif' => false,
        ]);

        $suggestions = $this->service->findSuggestions($this->garage->id, 'Vidange moteur');

        $this->assertCount(0, $suggestions);
    }

    public function test_returns_empty_for_no_match(): void
    {
        $suggestions = $this->service->findSuggestions($this->garage->id, 'peinture carrosserie');

        $this->assertCount(0, $suggestions);
    }
}
