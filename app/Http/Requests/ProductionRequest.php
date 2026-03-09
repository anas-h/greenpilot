<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_dechet_id' => 'required|exists:types_dechets,id',
            'conteneur_id' => 'nullable|exists:conteneurs,id',
            'quantite' => 'required|numeric|gt:0',
            'unite' => 'required|string|in:kg,litres,unites,tonnes,m3',
            'date_production' => 'required|date|before_or_equal:today',
            'source_type' => 'nullable|string|in:manuelle,scan_qr,import_or,correction,scan_barcode',
            'reference_externe' => 'nullable|string|max:100',
            'vehicule_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'quantite.gt' => 'La quantite doit etre superieure a zero.',
            'date_production.before_or_equal' => 'La date de production ne peut pas etre dans le futur.',
            'type_dechet_id.exists' => 'Le type de dechet selectionne est invalide.',
            'conteneur_id.exists' => 'Le conteneur selectionne est invalide.',
        ];
    }
}
