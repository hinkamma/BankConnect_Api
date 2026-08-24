<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProgrammerVirementRequest extends FormRequest
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
            'source_account_id'   => 'required|integer|exists:accounts,id',
            'account_number_dest' => 'required|string|exists:accounts,account_number',
            'amount'              => 'required|numeric|min:1',
            'description'         => 'nullable|string|max:255',
            'scheduled_date'      => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'source_account_id.required'   => "Le compte source est obligatoire.",
            'source_account_id.exists'     => "Le compte source sélectionné n'existe pas.",

            'account_number_dest.required' => "Le numéro de compte du destinataire est obligatoire.",
            'account_number_dest.exists'   => "Le compte destinataire est introuvable.",

            'amount.required'              => "Le montant est obligatoire.",
            'amount.numeric'               => "Le montant doit être un nombre.",
            'amount.min'                   => "Le montant doit être supérieur à 0.",

            'description.max'              => "La description ne doit pas dépasser 255 caractères.",

            'scheduled_date.required'      => "La date de programmation est obligatoire.",
            'scheduled_date.date'          => "La date fournie n'est pas valide.",
            'scheduled_date.after'         => "La date programmée doit être postérieure à aujourd'hui.",
        ];
    }
}
