<?php

namespace Tests\Feature;

use App\Models\FicheSecurite;
use App\Models\TypeDechet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FicheSecuriteTest extends TestCase
{
    private TypeDechet $typeDechet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestEnvironment();

        $this->typeDechet = TypeDechet::where('dangereux', true)->first();
    }

    public function test_can_list_fiches_securite(): void
    {
        FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Huile moteur usagee',
            'fournisseur' => 'Total',
            'fichier_path' => 'fds/1/test.pdf',
            'date_emission' => '2024-01-01',
            'date_validite' => '2027-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/fiches-securite', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_fiche_securite(): void
    {
        $fiche = FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Huile moteur usagee',
            'fournisseur' => 'Total',
            'fichier_path' => 'fds/1/test.pdf',
            'date_emission' => '2024-01-01',
            'date_validite' => '2027-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson("/api/fiches-securite/{$fiche->id}", $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('nom_produit', 'Huile moteur usagee')
            ->assertJsonPath('fournisseur', 'Total');
    }

    public function test_can_create_fiche_securite_with_pdf_upload(): void
    {
        Storage::fake('local');

        $response = $this->actingAsAdmin()
            ->postJson('/api/fiches-securite', [
                'type_dechet_id' => $this->typeDechet->id,
                'nom_produit' => 'Liquide de frein',
                'fournisseur' => 'Bosch',
                'date_emission' => '2025-01-15',
                'date_validite' => '2028-01-15',
                'fichier' => UploadedFile::fake()->create('fds.pdf', 500, 'application/pdf'),
            ], $this->withGarageHeader());

        $response->assertStatus(201)
            ->assertJsonPath('nom_produit', 'Liquide de frein')
            ->assertJsonPath('fournisseur', 'Bosch')
            ->assertJsonPath('actif', true);

        $this->assertDatabaseHas('fiches_securite', [
            'nom_produit' => 'Liquide de frein',
            'garage_id' => $this->garage->id,
        ]);
    }

    public function test_create_deactivates_existing_fds_for_same_type(): void
    {
        Storage::fake('local');

        $existingFiche = FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Old FDS',
            'fichier_path' => 'fds/1/old.pdf',
            'date_emission' => '2023-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/fiches-securite', [
                'type_dechet_id' => $this->typeDechet->id,
                'nom_produit' => 'New FDS',
                'date_emission' => '2025-01-15',
                'fichier' => UploadedFile::fake()->create('new-fds.pdf', 500, 'application/pdf'),
            ], $this->withGarageHeader());

        $existingFiche->refresh();
        $this->assertFalse($existingFiche->actif);
    }

    public function test_can_update_fiche_securite(): void
    {
        $fiche = FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Huile moteur',
            'fichier_path' => 'fds/1/test.pdf',
            'date_emission' => '2024-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->putJson("/api/fiches-securite/{$fiche->id}", [
                'nom_produit' => 'Huile moteur mise a jour',
                'fournisseur' => 'Shell',
            ], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('nom_produit', 'Huile moteur mise a jour')
            ->assertJsonPath('fournisseur', 'Shell');
    }

    public function test_can_delete_fiche_securite(): void
    {
        Storage::fake('local');

        $fiche = FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Fiche a supprimer',
            'fichier_path' => 'fds/1/delete-me.pdf',
            'date_emission' => '2024-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/fiches-securite/{$fiche->id}", [], $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonPath('message', 'Fiche de securite supprimee.');

        $this->assertSoftDeleted('fiches_securite', ['id' => $fiche->id]);
    }

    public function test_create_validates_file_must_be_pdf(): void
    {
        Storage::fake('local');

        $response = $this->actingAsAdmin()
            ->postJson('/api/fiches-securite', [
                'type_dechet_id' => $this->typeDechet->id,
                'nom_produit' => 'Test FDS',
                'date_emission' => '2025-01-15',
                'fichier' => UploadedFile::fake()->create('document.docx', 500, 'application/msword'),
            ], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors('fichier');
    }

    public function test_create_validates_required_fields(): void
    {
        Storage::fake('local');

        $response = $this->actingAsAdmin()
            ->postJson('/api/fiches-securite', [], $this->withGarageHeader());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type_dechet_id', 'nom_produit', 'fichier']);
    }

    public function test_can_filter_fiches_by_search(): void
    {
        FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Produit Alpha Specifique',
            'fournisseur' => 'Total',
            'fichier_path' => 'fds/1/test.pdf',
            'date_emission' => '2024-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        FicheSecurite::create([
            'garage_id' => $this->garage->id,
            'type_dechet_id' => $this->typeDechet->id,
            'nom_produit' => 'Liquide de frein',
            'fournisseur' => 'Bosch',
            'fichier_path' => 'fds/1/test2.pdf',
            'date_emission' => '2024-01-01',
            'actif' => true,
            'cree_par' => $this->admin->id,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/fiches-securite?search=Alpha', $this->withGarageHeader());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_fiches_securite_require_authentication(): void
    {
        $response = $this->getJson('/api/fiches-securite', $this->withGarageHeader());

        $response->assertUnauthorized();
    }
}
