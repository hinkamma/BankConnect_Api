<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatebeneficiaireRequest extends FormRequest
{
   public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => "le surnom du bénéficiaire est obligatoire",
            'nickname.max' => "le surnom ne doit pas dépasser 255 caractères",
        ];
    }
}
