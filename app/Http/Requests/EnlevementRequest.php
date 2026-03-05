<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnlevementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collecteur_id' => 'required|exists:collecteurs,id',
            'date_prevue' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'recurrence' => 'nullable|string|in:hebdomadaire,bimensuel,mensuel,bimestriel,trimestriel,semestriel,annuel',

            'lignes' => 'required|array|min:1',
            'lignes.*.type_dechet_id' => 'required|exists:types_dechets,id',
            'lignes.*.conteneur_id' => 'nullable|exists:conteneurs,id',
            'lignes.*.quantite_prevue' => 'required|numeric|gt:0',
            'lignes.*.unite' => 'required|string|in:kg,litres,unites,tonnes,m3',
        ];
    }

    public function messages(): array
    {
        return [
            'collecteur_id.required' => 'Le collecteur est requis.',
            'collecteur_id.exists' => 'Le collecteur selectionne est invalide.',
            'date_prevue.required' => 'La date prevue est requise.',
            'lignes.required' => 'Au moins une ligne est requise.',
            'lignes.min' => 'Au moins une ligne est requise.',
            'lignes.*.quantite_prevue.gt' => 'La quantite prevue doit etre superieure a zero.',
        ];
    }
}
