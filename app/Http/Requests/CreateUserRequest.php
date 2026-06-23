<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role_id' => 'required|exists:roles,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'designation' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'password' => 'required|min:8|confirmed',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'Selected role is invalid.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.unique' => 'This mobile number is already registered.',
            'designation.required' => 'Designation is required.',
            'department_id.required' => 'Please select a department.',
            'location_id.required' => 'Please select a location.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'status.required' => 'Please select a status.',
        ];
    }
}