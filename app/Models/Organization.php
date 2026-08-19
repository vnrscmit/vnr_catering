<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_name',
        'short_name',
        'address',
        'state',
        'district',
        'tehsil',
        'city_village',
        'pincode',
        'logo',
        'status',
        'max_location_allowed',
        'max_user_per_location',
        'gstin'
    ];

    protected $casts = [
        'status' => 'boolean',
        'max_location_allowed' => 'integer',
        'max_user_per_location' => 'integer'
    ];

    // Accessor for logo path
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/organizations/' . $this->logo);
        }
        return asset('default-logo.png');
    }

  
}
