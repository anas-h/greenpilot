<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GarageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'siret_etablissement' => 'nullable|string|size:14',
            'adresse' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'ville' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'surface_atelier' => 'nullable|integer|min:0',
            'regime_icpe' => 'nullable|in:non_classe,declaration,enregistrement',
            'activites' => 'nullable|array',
            'activites.*' => 'in:mecanique,carrosserie,peinture,pneumatique',
        ];
    }
}
