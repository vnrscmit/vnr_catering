<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'organization_name' => 'required|string|max:255|unique:organizations',
            'short_name' => 'required|string|max:50|unique:organizations',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'town' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
            'max_location_allowed' => 'required|integer|min:1',
            'max_user_per_location' => 'required|integer|min:1',
            'gstin' => 'nullable|string|max:15|unique:organizations',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'organization_name' => 'organization name',
            'short_name' => 'short name',
            'max_location_allowed' => 'max locations allowed',
            'max_user_per_location' => 'max users per location',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'organization_name.required' => 'The organization name is required.',
            'organization_name.unique' => 'This organization name already exists.',
            'short_name.required' => 'The short name is required.',
            'short_name.unique' => 'This short name already exists.',
            'logo.image' => 'The logo must be an image.',
            'logo.mimes' => 'The logo must be a JPEG, PNG, JPG or GIF.',
            'logo.max' => 'The logo may not be greater than 2 MB.',
            'gstin.unique' => 'This GSTIN already exists.',
        ];
    }
}
