<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInforUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // Identité & Contact
            'first_name'     => 'nullable|string|max:255',
            'last_name'      => 'nullable|string|max:255',
            'name'           => 'nullable|string|max:255',
            'username'       => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],

            // 💡 RÈGLE SIMPLIFIÉE : Retrait de 'sometimes' + Spécification exacte de la colonne 'email'
            'email'          => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'          => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],

            // Informations Personnelles
            'gender'         => 'nullable|string|in:M,F,autre',
            'date_of_birth'  => 'nullable|date',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality'    => 'nullable|string|max:255',
            'profession'     => 'nullable|string|max:255',

            // Adresse
            'adress'         => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:255',

            // Pièce d'identité
            'national_id'    => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Cette adresse e-mail est déjà utilisée.',
            'phone.unique'       => 'Ce numéro de téléphone est déjà utilisé.',
            'username.unique'    => 'Ce nom d\'utilisateur est déjà pris.',
            'gender.in'          => 'Le genre sélectionné doit être M, F ou autre.',
            'date_of_birth.date' => 'La date de naissance doit être une date valide.',
        ];
    }
}
