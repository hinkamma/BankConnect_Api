<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
   public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'=>'required|string|max:100',
            'last_name'=>'required|string|max:100',
            'email'=>'required|string|email|max:255|unique:users,email',
            'password'=>'required|string|min:8|confirmed',
            'phone'=>'nullable|string|unique:users,phone',
            'role'=>'required|in:client,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'=>'L\'adresse email est obligatoire.',
            'email.unique'=>'cet email est deja utilisé.',
            'password.reuired'=>'le mot de passe est obligatoire.',
            'password.confirmed'=>'La confirmation du mot de passe ne correspond pas.'
        ];
    }
}
