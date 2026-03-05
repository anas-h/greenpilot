<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollecteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $collecteurId = $this->route('collecteur')?->id;

        return [
            'raison_sociale' => 'required|string|max:255',
            'siret' => 'required|string|size:14',
            'adresse' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'ville' => 'required|string|max:255',
            'numero_autorisation' => 'required|string|max:100',
            'date_validite_autorisation' => 'required|date',
            'autorisation_adr' => 'nullable|boolean',
            'numero_adr' => 'nullable|string|max:100|required_if:autorisation_adr,true',
            'eco_organisme' => 'nullable|string|max:100',
            'contact_nom' => 'nullable|string|max:255',
            'contact_telephone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:255',
            'notes' => 'nullable|string|max:1000',
            'actif' => 'nullable|boolean',

            'tarifs' => 'nullable|array',
            'tarifs.*.type_dechet_id' => 'required_with:tarifs|exists:types_dechets,id',
            'tarifs.*.prix_unitaire' => 'nullable|numeric|min:0',
            'tarifs.*.prix_forfaitaire' => 'nullable|numeric|min:0',
            'tarifs.*.est_rachat' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'siret.size' => 'Le SIRET doit comporter exactement 14 caracteres.',
            'numero_adr.required_if' => 'Le numero ADR est requis lorsque l\'autorisation ADR est active.',
            'tarifs.*.type_dechet_id.exists' => 'Le type de dechet selectionne est invalide.',
        ];
    }
}
