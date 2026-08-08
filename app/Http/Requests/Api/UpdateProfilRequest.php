<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return $this->user() !== null; // Autoriser la requête si l'utilisateur est connecté
    }

    /**
     * Obtenir les règles de validation appliquées à la requête.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Récupérer l'utilisateur connecté
        $userId = $this->user()->id;

        return [
            // Identité & Contact
            'first_name'     => 'nullable|string|max:255',
            'last_name'      => 'nullable|string|max:255',
            'name'           => 'nullable|string|max:255',
            'username'       => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'email'          => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone'          => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users')->ignore($userId)],

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

    /**
     * Personnaliser les messages d'erreur (Optionnel)
     */
    public function messages(): array
    {
        return [
            'email.unique'    => 'Cet adresse e-mail est déjà utilisée.',
            'phone.unique'    => 'Ce numéro de téléphone est déjà utilisé.',
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'gender.in'       => 'Le genre sélectionné doit être M, F ou autre.',
            'date_of_birth.date' => 'La date de naissance doit être une date valide.',
        ];
    }
}