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
            'firstname.max:255' => 'The firstname must be more than 255',
            'lastname.required' => 'The lastname is required',
            'lastname.string' => 'The lastname must be a string',
            'lastname.max:255' => 'The lastname must be more than 255',
            'email.required' => 'The email is required',
            'email.email' => 'The email must be an email',
            'email.unique' => 'The email must be unique',
            'phone.string' => 'The phone must be a string',
            'phone.max:30' => 'The phone must be more than 30',
            'country.string' => 'The country must be a string',
            'country.max:100' => 'The country must be more than 100',
            'avatarUrl.string' => 'The avatarUrl must be a string',
            'password.required' => 'The password is required',
            'password.string' => 'The password must be a string',
            'password.min:8' => 'The password must be more than 8',
            'password.confirmed' => 'The password must be confirmed',
        ];
    }
}
