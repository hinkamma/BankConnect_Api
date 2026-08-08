<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddbeneficiaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    public function rules(): array
    {
        return [
            'account_number' => 'required|string|exists:accounts,account_number',
            'nickname' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'account_number.required' => "le numéro de compte est obligatoire",
            'account_number.exists' => "aucun compte ne correspond à ce numéro",

            'nickname.required' => "le surnom du bénéficiaire est obligatoire",
            'nickname.max' => "le surnom ne doit pas dépasser 255 caractères",
        ];
    }
}
