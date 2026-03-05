<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productions' => 'required|array|min:1|max:50',
            'productions.*.type_dechet_id' => 'required|exists:types_dechets,id',
            'productions.*.conteneur_id' => 'nullable|exists:conteneurs,id',
            'productions.*.quantite' => 'required|numeric|gt:0',
            'productions.*.unite' => 'required|string|in:kg,litres,unites,tonnes,m3',
            'productions.*.date_production' => 'required|date|before_or_equal:today',
            'productions.*.source_type' => 'nullable|string|in:manuelle,scan_qr,import_or,correction',
            'productions.*.reference_externe' => 'nullable|string|max:100',
            'productions.*.vehicule_info' => 'nullable|string|max:255',
            'productions.*.notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'productions.min' => 'Au moins une production est requise.',
            'productions.max' => 'Maximum 50 productions par lot.',
            'productions.*.quantite.gt' => 'La quantite de chaque production doit etre superieure a zero.',
            'productions.*.date_production.before_or_equal' => 'La date de production ne peut pas etre dans le futur.',
        ];
    }
}
