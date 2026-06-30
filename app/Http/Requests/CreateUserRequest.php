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
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'required|string|max:20|unique:users,mobile',
            'designation' => 'nullable|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'password' => 'required|min:8|confirmed',
            'status' => 'required|boolean',
            'personal_guest_flag' => 'nullable|boolean',
            'max_personal_guest_allowed' => 'nullable|integer|min:0|required_if:personal_guest_flag,1',
            'max_office_guest_allowed' => 'nullable|integer|min:0|required_if:personal_guest_flag,1',
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
            'max_personal_guest_allowed.required_if' => 'Personal guest count is required when guest access is enabled.',
            'max_office_guest_allowed.required_if' => 'Office guest count is required when guest access is enabled.',
            'max_personal_guest_allowed.integer' => 'Personal guest count must be a number.',
            'max_office_guest_allowed.integer' => 'Office guest count must be a number.',
            'max_personal_guest_allowed.min' => 'Personal guest count cannot be negative.',
            'max_office_guest_allowed.min' => 'Office guest count cannot be negative.',
        ];
    }
}