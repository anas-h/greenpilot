<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'raison_sociale' => 'required|string|max:255',
            'siret' => 'required|string|size:14|unique:entreprises,siret',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:255',
            'plan' => 'required|in:standard,premium',
        ];
    }

    public function messages(): array
    {
        return [
            'siret.size' => 'Le SIRET doit contenir exactement 14 chiffres.',
            'siret.unique' => 'Ce SIRET est deja enregistre.',
            'email.unique' => 'Cet email est deja utilise.',
        ];
    }
}
