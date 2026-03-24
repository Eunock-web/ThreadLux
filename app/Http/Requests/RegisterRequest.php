<?php

namespace App\Http\Requests;

// use Illuminate\Contracts\Validation\ValidationRule;
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
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'avatarUrl' => ['nullable','string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }


    public function messages() : array
    {
        return [
            'firstname.required' => 'The firstname is required',
            'firstname.string' => 'The firstname must be a string',
            'firstname.max:255' => 'The firstname must be more than 255'
        ];
    }
}
