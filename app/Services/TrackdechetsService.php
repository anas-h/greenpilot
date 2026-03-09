<?php

namespace App\Services;

use App\Models\Bordereau;
use App\Models\ConfigTrackdechets;
use App\Models\Garage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackdechetsService
{
    private string $baseUrl;

    private string $apiToken;

    public function __construct(private ConfigTrackdechets $config)
    {
        $this->baseUrl = $config->environnement === 'production'
            ? 'https://api.trackdechets.beta.gouv.fr'
            : 'https://api.sandbox.trackdechets.beta.gouv.fr';
        $this->apiToken = $config->api_token;
    }

    public static function forGarage(int $garageId): ?self
    {
        $entrepriseId = Garage::where('id', $garageId)->value('entreprise_id');
        if (! $entrepriseId) {
            return null;
        }

        return self::forEntreprise($entrepriseId);
    }

    public static function forEntreprise(int $entrepriseId): ?self
    {
        $config = ConfigTrackdechets::where('entreprise_id', $entrepriseId)->where('actif', true)->first();

        return $config ? new self($config) : null;
    }

    private function graphql(string $query, array $variables = []): array
    {
        $body = ['query' => $query];
        if (! empty($variables)) {
            $body['variables'] = $variables;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
            'Content-Type' => 'application/json',
            'apollo-require-preflight' => 'true',
        ])->post($this->baseUrl, $body);

        if ($response->failed()) {
            Log::error('Trackdechets API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Trackdechets API error: '.$response->status());
        }

        $data = $response->json();
        if (isset($data['errors'])) {
            Log::error('Trackdechets GraphQL errors', $data['errors']);
            throw new \RuntimeException('Trackdechets: '.($data['errors'][0]['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    // ─── BSDD (via createForm / markAsSealed / signEmissionForm) ────────

    public function createBsdd(Bordereau $bsd): array
    {
        $query = 'mutation CreateForm($createFormInput: CreateFormInput!) {
            createForm(createFormInput: $createFormInput) { id status }
        }';

        $emitterEmail = $bsd->garage->email ?? $bsd->garage->entreprise->email ?? 'contact@example.fr';
        $emitterContact = $bsd->garage->contact_nom ?? $bsd->garage->entreprise->raison_sociale;
        $emitterPhone = $bsd->garage->telephone ?? '0100000000';

        $input = [
            'emitter' => [
                'type' => 'PRODUCER',
                'company' => [
                    'siret' => $bsd->garage->siret_etablissement ?: $bsd->garage->entreprise->siret,
                    'name' => $bsd->garage->nom ?: $bsd->garage->entreprise->raison_sociale,
                    'address' => $bsd->garage->adresse.', '.$bsd->garage->code_postal.' '.$bsd->garage->ville,
                    'contact' => $emitterContact,
                    'phone' => $emitterPhone,
                    'mail' => $emitterEmail,
                ],
            ],
            'transporter' => [
                'company' => [
                    'siret' => $bsd->transporteur_siret,
                    'name' => $bsd->transporteur_raison_sociale,
                    'contact' => $bsd->transporteur_raison_sociale,
                    'phone' => '0100000000',
                    'mail' => $emitterEmail,
                ],
            ],
            'recipient' => [
                'company' => [
                    'siret' => $bsd->destination_siret,
                    'name' => $bsd->destination_raison_sociale,
                    'address' => $bsd->destination_adresse,
                    'contact' => $bsd->destination_raison_sociale,
                    'phone' => '0100000000',
                    'mail' => $emitterEmail,
                ],
                'processingOperation' => $bsd->code_traitement,
                'cap' => $bsd->cap ?: 'CAP-0000',
            ],
            'wasteDetails' => [
                'code' => trim($bsd->code_dechet),
                'name' => $bsd->denomination_dechet,
                'quantity' => round((float) $bsd->quantite / 1000, 4),
                'quantityType' => 'ESTIMATED',
                'isDangerous' => str_contains($bsd->code_dechet, '*'),
                'isSubjectToADR' => false,
                'consistences' => ['LIQUIDE'],
            ],
        ];

        return $this->graphql($query, ['createFormInput' => $input]);
    }

    public function publishBsdd(string $trackdechetsId): array
    {
        $query = 'mutation MarkAsSealed($id: ID!) { markAsSealed(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function signBsdd(Bordereau $bsd, string $signatureType = 'EMISSION'): array
    {
        $query = 'mutation SignEmissionForm($id: ID!, $input: SignEmissionFormInput!) {
            signEmissionForm(id: $id, input: $input) { id status }
        }';

        $bsd->loadMissing('creePar');
        $signerName = $bsd->creePar
            ? trim($bsd->creePar->nom.' '.$bsd->creePar->prenom)
            : 'Responsable';

        $packagingInfos = [];
        if ($bsd->nombre_colis && $bsd->conditionnement) {
            $packagingInfos[] = [
                'type' => $this->mapConditionnement($bsd->conditionnement),
                'quantity' => (int) $bsd->nombre_colis,
            ];
        } else {
            $packagingInfos[] = [
                'type' => 'AUTRE',
                'other' => 'Conteneur',
                'quantity' => 1,
            ];
        }

        return $this->graphql($query, [
            'id' => $bsd->trackdechets_id,
            'input' => [
                'quantity' => round((float) $bsd->quantite / 1000, 4),
                'emittedAt' => now()->toIso8601String(),
                'emittedBy' => $signerName,
                'packagingInfos' => $packagingInfos,
            ],
        ]);
    }

    public function getBsdd(string $trackdechetsId): array
    {
        $query = 'query Form($id: ID!) { form(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function getBsddPdf(string $trackdechetsId): ?string
    {
        $query = 'query FormPdf($id: ID!) { formPdf(id: $id) { downloadLink } }';
        $result = $this->graphql($query, ['id' => $trackdechetsId]);

        return $result['formPdf']['downloadLink'] ?? null;
    }

    // ─── BSDA (Amiante) ─────────────────────────────────────────────────

    public function createBsda(Bordereau $bsd): array
    {
        $query = 'mutation CreateDraftBsda($input: BsdaInput!) { createDraftBsda(input: $input) { id status } }';
        $input = [
            'emitter' => [
                'isPrivateIndividual' => false,
                'company' => [
                    'siret' => $bsd->garage->siret_etablissement ?: $bsd->garage->entreprise->siret,
                    'name' => $bsd->garage->nom ?: $bsd->garage->entreprise->raison_sociale,
                    'address' => $bsd->garage->adresse.', '.$bsd->garage->code_postal.' '.$bsd->garage->ville,
                    'contact' => $bsd->garage->contact_nom ?? '',
                    'phone' => $bsd->garage->telephone ?? '',
                    'mail' => $bsd->garage->email ?? '',
                ],
            ],
            'transporter' => [
                'company' => [
                    'siret' => $bsd->transporteur_siret,
                    'name' => $bsd->transporteur_raison_sociale,
                    'address' => $bsd->transporteur_adresse ?? '',
                ],
                'recepisse' => [
                    'number' => $bsd->transporteur_recepisse ?? '',
                ],
            ],
            'destination' => [
                'company' => [
                    'siret' => $bsd->destination_siret,
                    'name' => $bsd->destination_raison_sociale,
                    'address' => $bsd->destination_adresse ?? '',
                ],
                'cap' => $bsd->cap ?? '',
                'plannedOperationCode' => $bsd->code_traitement,
            ],
            'waste' => [
                'code' => $bsd->code_dechet,
                'adr' => $bsd->adr ?? '',
                'materialName' => $bsd->denomination_dechet,
                'consistence' => $this->mapConsistence($bsd->consistance),
            ],
            'weight' => [
                'value' => (float) ($bsd->quantite ?? 0),
                'isEstimate' => true,
            ],
            'packagings' => $bsd->nombre_colis ? [
                [
                    'type' => $this->mapConditionnement($bsd->conditionnement),
                    'quantity' => (int) $bsd->nombre_colis,
                ],
            ] : [],
        ];

        return $this->graphql($query, ['input' => $input]);
    }

    public function publishBsda(string $trackdechetsId): array
    {
        $query = 'mutation PublishBsda($id: ID!) { publishBsda(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function signBsda(Bordereau $bsd, string $signatureType = 'EMISSION'): array
    {
        $query = 'mutation SignBsda($id: ID!, $input: BsdaSignatureInput!) {
            signBsda(id: $id, input: $input) { id status }
        }';

        $bsd->loadMissing('creePar');
        $signerName = $bsd->creePar
            ? trim($bsd->creePar->nom.' '.$bsd->creePar->prenom)
            : 'Responsable';

        return $this->graphql($query, [
            'id' => $bsd->trackdechets_id,
            'input' => [
                'type' => $signatureType,
                'date' => now()->toIso8601String(),
                'author' => $signerName,
            ],
        ]);
    }

    public function getBsda(string $trackdechetsId): array
    {
        $query = 'query Bsda($id: ID!) { bsda(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function getBsdaPdf(string $trackdechetsId): ?string
    {
        $query = 'query BsdaPdf($id: ID!) { bsdaPdf(id: $id) { downloadLink } }';
        $result = $this->graphql($query, ['id' => $trackdechetsId]);

        return $result['bsdaPdf']['downloadLink'] ?? null;
    }

    // ─── BSVHU (VHU) ────────────────────────────────────────────────────

    public function createBsvhu(Bordereau $bsd): array
    {
        $query = 'mutation CreateDraftBsvhu($input: BsvhuInput!) { createDraftBsvhu(input: $input) { id status } }';
        $input = [
            'emitter' => [
                'agrementNumber' => $bsd->garage->numero_agrement ?? '',
                'company' => [
                    'siret' => $bsd->garage->siret_etablissement ?: $bsd->garage->entreprise->siret,
                    'name' => $bsd->garage->nom ?: $bsd->garage->entreprise->raison_sociale,
                    'address' => $bsd->garage->adresse.', '.$bsd->garage->code_postal.' '.$bsd->garage->ville,
                    'contact' => $bsd->garage->contact_nom ?? '',
                    'phone' => $bsd->garage->telephone ?? '',
                    'mail' => $bsd->garage->email ?? '',
                ],
            ],
            'transporter' => [
                'company' => [
                    'siret' => $bsd->transporteur_siret,
                    'name' => $bsd->transporteur_raison_sociale,
                    'address' => $bsd->transporteur_adresse ?? '',
                ],
                'recepisse' => [
                    'number' => $bsd->transporteur_recepisse ?? '',
                ],
            ],
            'destination' => [
                'type' => 'BROYEUR',
                'agrementNumber' => $bsd->destination_agrement ?? '',
                'company' => [
                    'siret' => $bsd->destination_siret,
                    'name' => $bsd->destination_raison_sociale,
                    'address' => $bsd->destination_adresse ?? '',
                ],
                'plannedOperationCode' => $bsd->code_traitement,
            ],
            'wasteCode' => $bsd->code_dechet,
            'packaging' => $this->mapVhuPackaging($bsd->conditionnement),
            'quantity' => (int) ($bsd->nombre_colis ?? 1),
            'weight' => [
                'value' => (float) ($bsd->quantite ?? 0),
                'isEstimate' => true,
            ],
            'identification' => [
                'numbers' => $bsd->identification_numbers ?? [],
                'type' => 'NUMERO_ORDRE_REGISTRE_POLICE',
            ],
        ];

        return $this->graphql($query, ['input' => $input]);
    }

    public function publishBsvhu(string $trackdechetsId): array
    {
        $query = 'mutation PublishBsvhu($id: ID!) { publishBsvhu(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function signBsvhu(Bordereau $bsd, string $signatureType = 'EMISSION'): array
    {
        $query = 'mutation SignBsvhu($id: ID!, $input: BsvhuSignatureInput!) {
            signBsvhu(id: $id, input: $input) { id status }
        }';

        $bsd->loadMissing('creePar');
        $signerName = $bsd->creePar
            ? trim($bsd->creePar->nom.' '.$bsd->creePar->prenom)
            : 'Responsable';

        return $this->graphql($query, [
            'id' => $bsd->trackdechets_id,
            'input' => [
                'type' => $signatureType,
                'date' => now()->toIso8601String(),
                'author' => $signerName,
            ],
        ]);
    }

    public function getBsvhu(string $trackdechetsId): array
    {
        $query = 'query Bsvhu($id: ID!) { bsvhu(id: $id) { id status } }';

        return $this->graphql($query, ['id' => $trackdechetsId]);
    }

    public function getBsvhuPdf(string $trackdechetsId): ?string
    {
        $query = 'query BsvhuPdf($id: ID!) { bsvhuPdf(id: $id) { downloadLink } }';
        $result = $this->graphql($query, ['id' => $trackdechetsId]);

        return $result['bsvhuPdf']['downloadLink'] ?? null;
    }

    // ─── Generic helpers ─────────────────────────────────────────────────

    /**
     * Create BSD on Trackdechets based on type_bsd.
     */
    public function createBsd(Bordereau $bsd): array
    {
        return match ($bsd->type_bsd) {
            'BSDD' => $this->createBsdd($bsd),
            'BSDA' => $this->createBsda($bsd),
            'BSVHU' => $this->createBsvhu($bsd),
            default => throw new \InvalidArgumentException("Type BSD non supporte: {$bsd->type_bsd}"),
        };
    }

    /**
     * Publish BSD on Trackdechets based on type_bsd.
     */
    public function publishBsd(Bordereau $bsd): array
    {
        return match ($bsd->type_bsd) {
            'BSDD' => $this->publishBsdd($bsd->trackdechets_id),
            'BSDA' => $this->publishBsda($bsd->trackdechets_id),
            'BSVHU' => $this->publishBsvhu($bsd->trackdechets_id),
            default => throw new \InvalidArgumentException("Type BSD non supporte: {$bsd->type_bsd}"),
        };
    }

    /**
     * Sign BSD on Trackdechets based on type_bsd.
     */
    public function signBsd(Bordereau $bsd, string $signatureType = 'EMISSION'): array
    {
        return match ($bsd->type_bsd) {
            'BSDD' => $this->signBsdd($bsd, $signatureType),
            'BSDA' => $this->signBsda($bsd, $signatureType),
            'BSVHU' => $this->signBsvhu($bsd, $signatureType),
            default => throw new \InvalidArgumentException("Type BSD non supporte: {$bsd->type_bsd}"),
        };
    }

    /**
     * Get BSD status from Trackdechets based on type_bsd.
     */
    public function getBsd(Bordereau $bsd): array
    {
        return match ($bsd->type_bsd) {
            'BSDD' => $this->getBsdd($bsd->trackdechets_id),
            'BSDA' => $this->getBsda($bsd->trackdechets_id),
            'BSVHU' => $this->getBsvhu($bsd->trackdechets_id),
            default => throw new \InvalidArgumentException("Type BSD non supporte: {$bsd->type_bsd}"),
        };
    }

    /**
     * Get PDF download link from Trackdechets based on type_bsd.
     */
    public function getBsdPdf(Bordereau $bsd): ?string
    {
        return match ($bsd->type_bsd) {
            'BSDD' => $this->getBsddPdf($bsd->trackdechets_id),
            'BSDA' => $this->getBsdaPdf($bsd->trackdechets_id),
            'BSVHU' => $this->getBsvhuPdf($bsd->trackdechets_id),
            default => throw new \InvalidArgumentException("Type BSD non supporte: {$bsd->type_bsd}"),
        };
    }

    /**
     * Search companies on the Trackdechets public registry.
     */
    public function searchCompanies(string $clue): array
    {
        $query = 'query SearchCompanies($clue: String!) {
            searchCompanies(clue: $clue) {
                siret
                name
                address
                naf
                companyTypes
                transporterReceipt {
                    receiptNumber
                    validityLimit
                }
            }
        }';

        $data = $this->graphql($query, ['clue' => $clue]);

        return $data['searchCompanies'] ?? [];
    }

    /**
     * Test connection to Trackdechets API.
     * Returns company info on success, throws on failure.
     */
    public function testConnection(): array
    {
        $query = '{ me { companies { siret name } } }';

        return $this->graphql($query);
    }

    // ─── Mapping helpers ─────────────────────────────────────────────────

    private function mapConsistence(?string $consistance): string
    {
        return match ($consistance) {
            'SOLIDE' => 'SOLIDE',
            'LIQUIDE' => 'LIQUIDE',
            'GAZEUX' => 'GAZEUX',
            'PATEUX' => 'PATEUX',
            default => 'SOLIDE',
        };
    }

    private function mapConditionnement(?string $conditionnement): string
    {
        return match (strtoupper($conditionnement ?? '')) {
            'FUT' => 'FUT',
            'GRV', 'IBC' => 'GRV',
            'CITERNE' => 'CITERNE',
            'BENNE' => 'BENNE',
            'BIG_BAG' => 'BIG_BAG',
            default => 'AUTRE',
        };
    }

    private function mapVhuPackaging(?string $conditionnement): string
    {
        return match (strtoupper($conditionnement ?? '')) {
            'LOT' => 'LOT',
            'UNITE' => 'UNITE',
            default => 'UNITE',
        };
    }
}
