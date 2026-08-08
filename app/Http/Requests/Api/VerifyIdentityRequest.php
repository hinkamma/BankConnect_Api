<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Autoriser la requête si l'utilisateur est connecté
    }

    public function rules(): array
    {
        return [
            'national_id' => 'required|string|max:100',
            'id_document' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Photo de la CNI / Passeport
            'selfie'      => 'required|image|mimes:jpeg,png,jpg|max:5120', // Selfie / Photo de profil
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required' => 'Le numéro de pièce d\'identité est obligatoire.',
            'id_document.required' => 'La photo de la pièce d\'identité est obligatoire.',
            'selfie.required'      => 'La photo de profil (selfie) est obligatoire.',
            'id_document.image'    => 'Le document d\'identité doit être une image.',
            'selfie.image'         => 'Le selfie doit être une image valide.',
        ];
    }
}
