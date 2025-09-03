<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->route('sub_user')->user_id === auth()->id();
    }

    public function rules(): array
    {
        $subUserId = $this->route('sub_user')->id;
        
        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('sub_users')->ignore($subUserId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('sub_users')->ignore($subUserId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive,suspended'],
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
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'balance.required' => 'Balance is required.',
            'balance.numeric' => 'Balance must be a number.',
            'balance.min' => 'Balance cannot be negative.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
