<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompletionEnlevementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_effective' => 'required|date|before_or_equal:today',
            'numero_bon_enlevement' => 'nullable|string|max:100',

            'lignes' => 'required|array|min:1',
            'lignes.*.ligne_id' => 'required|exists:lignes_enlevement,id',
            'lignes.*.quantite_effective' => 'required|numeric|gte:0',
        ];
    }

    public function messages(): array
    {
        return [
            'date_effective.required' => 'La date effective est requise.',
            'date_effective.before_or_equal' => 'La date effective ne peut pas etre dans le futur.',
            'lignes.required' => 'Les quantites effectives sont requises.',
            'lignes.*.ligne_id.exists' => 'La ligne d\'enlevement est invalide.',
            'lignes.*.quantite_effective.gte' => 'La quantite effective doit etre positive ou nulle.',
        ];
    }
}
