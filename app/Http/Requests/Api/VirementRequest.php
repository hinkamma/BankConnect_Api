<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VirementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_account_id'    => 'required|integer|exists:accounts,id',
            'account_number_dest'  => 'required|string|exists:accounts,account_number',
            'amount'               => 'required|numeric|min:100',
            'description'          => 'nullable|string|max:255'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'source_account_id.required'   => "Le compte source est requis.",
            'source_account_id.exists'     => "Le compte source sélectionné n'existe pas.",
            'account_number_dest.required' => "Le numéro de compte destinataire est requis.",
            'account_number_dest.exists'   => "Le numéro de compte destinataire est introuvable.",
            'amount.required'              => "Le montant est requis.",
            'amount.min'                   => "Le montant minimum pour un virement est de 100 FCFA.",
            'amount.numeric'               => "Le montant doit être un nombre valide."
        ];
    }
}
