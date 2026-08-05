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
            'account_number_dest'=>'required|string|exists:accounts,account_number',
            'amount'=>'required|numeric|min:100',
            'description'=>'nullable|string|max:255'
        ];
    }

    public function messages():array{
        return [
            'account_number_dest.required'=>"le numero de compte est requis",
            'amount.required'=>"le montant est requis"
        ];
    }
}
