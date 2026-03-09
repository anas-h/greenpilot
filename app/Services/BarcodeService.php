<?php

namespace App\Services;

use App\Models\TypeDechet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BarcodeService
{
    private const CATEGORY_KEYWORDS = [
        'huile' => ['oil', 'huile', 'lubricant', 'lubrifiant', 'motor-oil', 'engine-oil', '5w', '10w', '15w'],
        'filtre' => ['filter', 'filtre', 'filtration'],
        'batterie' => ['battery', 'batterie', 'accumulateur', 'accumulator'],
        'pneu' => ['tire', 'tyre', 'pneu', 'pneumatique'],
        'solvant' => ['solvent', 'solvant', 'degreaser', 'degraissant', 'acetone', 'thinner', 'diluant', 'white-spirit'],
        'absorbant' => ['absorbant', 'absorbent', 'chiffon', 'wipe', 'cloth'],
        'emballage' => ['packaging', 'emballage', 'bidon', 'can', 'drum', 'fut', 'jerrican', 'container'],
        'metal' => ['metal', 'steel', 'acier', 'aluminium', 'fer', 'iron', 'copper', 'cuivre'],
        'verre' => ['glass', 'verre', 'vitre', 'windshield', 'pare-brise'],
        'plastique' => ['plastic', 'plastique', 'polypropylene', 'polyethylene'],
        'fluide' => ['fluid', 'fluide', 'coolant', 'liquide-de-refroidissement', 'brake-fluid', 'liquide-de-frein', 'antigel', 'antifreeze', 'adblue'],
        'peinture' => ['paint', 'peinture', 'coating', 'vernis', 'laque', 'primer', 'appret', 'spray'],
    ];

    /**
     * Look up a product by EAN code via Open Products Facts or Open Food Facts.
     */
    public function lookupProduct(string $ean): ?array
    {
        // Try Open Products Facts first (non-food products)
        $response = Http::timeout(5)->get("https://world.openproductsfacts.org/api/v2/product/{$ean}.json");

        if ($response->ok()) {
            $data = $response->json();
            if (($data['status'] ?? 0) == 1 && !empty($data['product'])) {
                return $data['product'];
            }
        }

        // Fallback to Open Food Facts (broader database)
        $response = Http::timeout(5)->get("https://world.openfoodfacts.org/api/v2/product/{$ean}.json");

        if ($response->ok()) {
            $data = $response->json();
            if (($data['status'] ?? 0) == 1 && !empty($data['product'])) {
                return $data['product'];
            }
        }

        return null;
    }

    /**
     * Match product data to a TypeDechet category using keyword matching.
     */
    public function matchCategory(array $product): string
    {
        $searchText = strtolower(implode(' ', array_filter([
            $product['product_name'] ?? '',
            $product['product_name_fr'] ?? '',
            $product['brands'] ?? '',
            $product['categories'] ?? '',
            implode(' ', $product['categories_tags'] ?? []),
        ])));

        foreach (self::CATEGORY_KEYWORDS as $categorie => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($searchText, strtolower($keyword))) {
                    return $categorie;
                }
            }
        }

        return 'autre';
    }

    /**
     * Find the best matching TypeDechet for a category in a given garage.
     */
    public function findTypeDechet(string $categorie, int $garageId): ?TypeDechet
    {
        // First: active types for this garage with matching category
        $type = TypeDechet::where('categorie', $categorie)
            ->whereHas('garages', function ($q) use ($garageId) {
                $q->where('garage_id', $garageId)->where('garage_type_dechet.actif', true);
            })
            ->first();

        if ($type) {
            return $type;
        }

        // Fallback: any type with matching category (system or enterprise)
        return TypeDechet::where('categorie', $categorie)->first();
    }

    /**
     * Estimate quantity from product data.
     */
    public function estimateQuantity(array $product, string $unite): ?float
    {
        $quantity = $product['quantity'] ?? $product['product_quantity'] ?? null;
        if (!$quantity) {
            return null;
        }

        $qty = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', (string) $quantity));
        if ($qty <= 0) {
            return null;
        }

        // Convert ml to litres
        $qtyStr = strtolower((string) ($product['quantity'] ?? ''));
        if ($unite === 'litres' && str_contains($qtyStr, 'ml')) {
            $qty = $qty / 1000;
        }
        // Convert g to kg
        if ($unite === 'kg' && str_contains($qtyStr, 'g') && !str_contains($qtyStr, 'kg')) {
            $qty = $qty / 1000;
        }

        return round($qty, 3);
    }
}
