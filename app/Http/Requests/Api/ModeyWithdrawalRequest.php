<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ModeyWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !=null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "montant"=>"required|numeric|min:100",
            "description"=>"nullable|string|max:255"
        ];
    }

    public function messages():array
    {
        return [
            "montant.required"=> "Le montant est obligatoire."
        ];
    }
}
