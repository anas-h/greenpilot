<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('managedUser')?->id;

        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($userId)],
            'password' => $this->isMethod('POST') ? 'required|string|min:8' : 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,chef_atelier,mecanicien,comptable',
            'garages_ids' => 'nullable|array',
            'garages_ids.*' => 'integer|exists:garages,id',
        ];
    }
}
