<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:sub_users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:sub_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'balance.numeric' => 'Balance must be a number.',
            'balance.min' => 'Balance cannot be negative.',
        ];
    }
}
