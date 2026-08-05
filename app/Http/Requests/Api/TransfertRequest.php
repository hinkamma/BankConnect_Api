<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransfertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'source_account_id' => 'sometimes|exists:accounts,id', // optionnel
            'target_account_number' => 'required|string|exists:accounts,account_number',
            'amount' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'target_account_number.required' => 'Le numéro de compte destinataire est requis.',
            'target_account_number.exists' => 'Ce numéro de compte n\'existe pas.',
            'amount.min' => 'Le montant minimum est de 100 XAF.',
            'source_account_id.exists' => 'Le compte source sélectionné n\'existe pas.',
        ];
    }
}
