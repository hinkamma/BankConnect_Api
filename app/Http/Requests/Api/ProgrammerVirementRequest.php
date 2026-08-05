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
        return $this->user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_number_dest' => 'required|string|exists:accounts,account_number',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
            'scheduled_date' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'account_number_dest.required' => "le numéro de compte du destinataire est obligatoire",
            'account_number_dest.exists' => "le compte destinataire est introuvable",

            'amount.required' => "le montant est obligatoire",
            'amount.numeric' => "le montant doit être un nombre",
            'amount.min' => "le montant doit être supérieur à 0",

            'description.max' => "la description ne doit pas dépasser 255 caractères",

            'scheduled_date.required' => "la date de programmation est obligatoire",
            'scheduled_date.date' => "la date fournie n'est pas valide",
            'scheduled_date.after' => "la date programmée doit être postérieure à aujourd'hui",
        ];
    }
}
