<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        // Get the user ID from the route
        $userId = $this->route('id');

        // Prevent the logged-in user from updating their own account
        if ($userId == Auth::id()) {
            return false; // Deny access
        }

        return true;
    }

    public function rules()
    {
        $userId = $this->route('id'); // Get the user ID from the route
        $roleId = $this->input('role_id');
        $isCanteenAdmin = false;

        // Check if the selected role is Canteen Administrator
        if ($roleId) {
            $role = \App\Models\Role::find($roleId);
            if ($role && $role->name == 'Canteen Administrator') {
                $isCanteenAdmin = true;
            }
        }

        return [
            // User Information
            'role_id' => 'required|exists:roles,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'generate_code' => 'required_if:role_id,2|nullable|digits:4',

            // User Code - Conditional based on role
            'user_code' => [
                $isCanteenAdmin ? 'nullable' : 'required',
                'string',
                'max:5',
                'min:1',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('users', 'user_code')->ignore($userId),
            ],

            // Email - Conditional based on role
            'email' => [
                $isCanteenAdmin ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            // Mobile - Conditional based on role
            'mobile' => [
                $isCanteenAdmin ? 'nullable' : 'required',
                'string',
                'max:20',
                Rule::unique('users', 'mobile')->ignore($userId),
            ],

            // Username - Always required
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId),
            ],

            // Professional Information
            'designation' => 'nullable|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'other_location_id' => 'nullable|array',
            'other_location_id.*' => 'exists:locations,id',

            // Security & Payment
            'security_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:Cash,UPI,Both',
            'deposit_date' => 'nullable|date|before_or_equal:today',

            // Guest Settings
            'personal_guest_flag' => 'nullable|boolean',
            'max_personal_guest_allowed' => 'nullable|integer|min:0|required_if:personal_guest_flag,1',
            'max_office_guest_allowed' => 'nullable|integer|min:0|required_if:personal_guest_flag,1',

            // Status & Flags
            'president_flag' => 'nullable|boolean',
            'status' => 'required|boolean',

            // Password (optional during update)
            'password' => 'nullable|min:8|max:8|confirmed',
            'password_confirmation' => 'nullable|min:8|max:8',
        ];
    }

    public function messages()
    {
        return [
            // Role Messages
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'Selected role is invalid.',

            // Username Messages
            'username.required' => 'Username is required.',
            'username.string' => 'Username must be a string.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username must not exceed 50 characters.',
            'username.unique' => 'This username is already taken.',

            'generate_code.required_if' => 'Generate code is required for Canteen Incharge.',
            'generate_code.digits' => 'Generate code must be exactly 4 digits.',

            // User Code Messages
            'user_code.required' => 'Employee ID is required.',
            'user_code.string' => 'Employee ID must be a string.',
            'user_code.max' => 'Employee ID must not exceed 5 characters.',
            'user_code.min' => 'Employee ID must be at least 1 character.',
            'user_code.regex' => 'Employee ID must contain only alphanumeric characters.',
            'user_code.unique' => 'This Employee ID is already taken.',

            // Name Messages
            'first_name.required' => 'Full name is required.',
            'first_name.string' => 'Full name must be a string.',
            'first_name.max' => 'Full name must not exceed 255 characters.',
            'middle_name.string' => 'Middle name must be a string.',
            'middle_name.max' => 'Middle name must not exceed 255 characters.',
            'last_name.string' => 'Last name must be a string.',
            'last_name.max' => 'Last name must not exceed 255 characters.',

            // Email Messages
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'email.unique' => 'This email is already registered.',

            // Mobile Messages
            'mobile.required' => 'Mobile number is required.',
            'mobile.string' => 'Mobile number must be a string.',
            'mobile.max' => 'Mobile number must not exceed 20 characters.',
            'mobile.unique' => 'This mobile number is already registered.',

            // Professional Messages
            'designation.string' => 'Designation must be a string.',
            'designation.max' => 'Designation must not exceed 255 characters.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists' => 'Selected department is invalid.',
            'location_id.required' => 'Please select a base location.',
            'location_id.exists' => 'Selected base location is invalid.',
            'other_location_id.array' => 'Additional locations must be an array.',
            'other_location_id.*.exists' => 'Selected additional location is invalid.',

            // Security & Payment Messages
            'security_amount.numeric' => 'Security amount must be a number.',
            'security_amount.min' => 'Security amount cannot be negative.',
            'payment_method.in' => 'Payment method must be Cash, UPI, or Both.',
            'deposit_date.date' => 'Please enter a valid date.',
            'deposit_date.before_or_equal' => 'Deposit date cannot be in the future.',

            // Guest Messages
            'max_personal_guest_allowed.required_if' => 'Personal guest count is required when guest access is enabled.',
            'max_office_guest_allowed.required_if' => 'Office guest count is required when guest access is enabled.',
            'max_personal_guest_allowed.integer' => 'Personal guest count must be a number.',
            'max_office_guest_allowed.integer' => 'Office guest count must be a number.',
            'max_personal_guest_allowed.min' => 'Personal guest count cannot be negative.',
            'max_office_guest_allowed.min' => 'Office guest count cannot be negative.',

            // Status Messages
            'status.required' => 'Please select a status.',
            'status.boolean' => 'Status must be either Active or Inactive.',
            'president_flag.boolean' => 'President flag must be either Yes or No.',

            // Password Messages
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.min' => 'Password confirmation must be at least 8 characters.',
            'password_confirmation.max' => 'Password confirmation must not exceed 8 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Format names
        $this->merge([
            'first_name' => $this->first_name ? ucwords(trim($this->first_name)) : null,
            'middle_name' => $this->middle_name ? ucwords(trim($this->middle_name)) : null,
            'last_name' => $this->last_name ? ucwords(trim($this->last_name)) : null,
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'user_code' => $this->user_code ? strtoupper(trim($this->user_code)) : null,
            'mobile' => $this->mobile ? trim($this->mobile) : null,
            'username' => $this->username ? strtolower(trim($this->username)) : null,
        ]);

        // Ensure password and confirmation are only set if password is provided
        if ($this->filled('password') && !$this->filled('password_confirmation')) {
            $this->merge([
                'password_confirmation' => $this->password
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $userId = $this->route('id');
            $user = \App\Models\User::find($userId);

            // Check if password is provided but confirmation is missing
            if ($this->filled('password') && !$this->filled('password_confirmation')) {
                $validator->errors()->add('password_confirmation', 'Please confirm your password.');
            }

            // Check if confirmation is provided but password is missing
            if ($this->filled('password_confirmation') && !$this->filled('password')) {
                $validator->errors()->add('password', 'Please enter a password.');
            }

            // Check if username is provided and not conflicting with another user
            if ($this->filled('username')) {
                $usernameExists = \App\Models\User::where('username', $this->username)
                    ->where('id', '!=', $userId)
                    ->exists();

                if ($usernameExists) {
                    $validator->errors()->add('username', 'This username is already taken.');
                }
            }

            // Check if user is trying to change their own role
            if ($user && $this->filled('role_id')) {
                // Prevent Super Admin from being demoted by anyone except themselves
                if ($user->role == 'Super Admin' && Auth::user()->role != 'Super Admin') {
                    $validator->errors()->add('role_id', 'You cannot change the role of a Super Admin.');
                }

                // Prevent users from changing their own role
                if ($userId == Auth::id() && $this->filled('role_id')) {
                    $validator->errors()->add('role_id', 'You cannot change your own role.');
                }
            }

            // Check if president flag can be changed
            if ($this->filled('president_flag') && $this->president_flag == 1 && $this->filled('location_id')) {
                $exists = \App\Models\User::where('location_id', $this->location_id)
                    ->where('president_flag', 1)
                    ->where('status', 1)
                    ->where('id', '!=', $userId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('president_flag', 'A Canteen President already exists for this location.');
                }
            }

            // Additional validation for Canteen Administrator
            $roleId = $this->input('role_id');
            if ($roleId) {
                $role = \App\Models\Role::find($roleId);
                if ($role && $role->name == 'Canteen Administrator') {
                    // Ensure email is null if not provided
                    if (empty($this->email)) {
                        $this->merge(['email' => null]);
                    }

                    // Ensure mobile is null if not provided
                    if (empty($this->mobile)) {
                        $this->merge(['mobile' => null]);
                    }

                    // Ensure user_code is null if not provided
                    if (empty($this->user_code)) {
                        $this->merge(['user_code' => null]);
                    }
                }
            }
        });
    }
}
