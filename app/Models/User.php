<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword
{
    use  HasApiTokens,  HasFactory, Notifiable;

    protected $fillable = [
        'role_id',           // Changed from 'role' to 'role_id' for foreign key
        'role',
        'first_name',
        'last_name',
        'email',
        'mobile',            // Added mobile field
        'designation',       // Added designation field
        'department_id',     // Added department foreign key
        'location_id',       // Added location foreign key
        'other_location_id',
        'personal_guest_flag',
        'max_personal_guest_allowed',
        'max_office_guest_allowed',
        'password',
        'status',
        'notice',
        'phone_number',
        'address',
        'activation_token',
        'two_factor_auth',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the role associated with the user.
     */
    public function roles()
    {
        return $this->belongsTo(Role::class, 'role_id'); // Changed to Role::class
    }

    /**
     * Get the department associated with the user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the location associated with the user.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute()
    {
        $name = $this->first_name;

        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }

        $name .= ' ' . $this->last_name;

        return $name;
    }

    /**
     * Get the user's full name with title.
     */
    public function getFullNameWithDesignationAttribute()
    {
        return $this->full_name . ' (' . $this->designation . ')';
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Check if user is active.
     */
    public function isActive()
    {
        return $this->status == 1;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->hasRole('Admin') || $this->hasRole('Administrator');
    }

    /**
     * Get the user's mobile number (alias for backward compatibility).
     */
    public function getMobileAttribute($value)
    {
        return $value;
    }

    /**
     * Set the user's mobile number (alias for backward compatibility).
     */
    public function setMobileAttribute($value)
    {
        $this->attributes['mobile'] = $value;
    }
}
