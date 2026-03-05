<?php

namespace App\Services;

use App\Models\MappingOperation;

class MappingService
{
    /**
     * Find mapping suggestions based on a description text.
     *
     * Searches through active mappings for the given garage and returns
     * any whose keyword (mot_cle) appears in the description.
     */
    public function findSuggestions(int $garageId, string $description): array
    {
        $mappings = MappingOperation::where('garage_id', $garageId)
            ->where('actif', true)
            ->with('typeDechet')
            ->get();

        $suggestions = [];
        $descLower = mb_strtolower($description);

        foreach ($mappings as $mapping) {
            if (str_contains($descLower, mb_strtolower($mapping->mot_cle))) {
                $suggestions[] = [
                    'mapping_id' => $mapping->id,
                    'mot_cle' => $mapping->mot_cle,
                    'type_dechet' => $mapping->typeDechet,
                    'quantite_defaut' => $mapping->quantite_defaut,
                    'unite' => $mapping->unite,
                ];
            }
        }

        return $suggestions;
    }
}
