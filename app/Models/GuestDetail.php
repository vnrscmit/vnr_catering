<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestDetail extends Model
{
    protected $fillable = [
        'guest_id',
        'guest_name',
        'department_id',
        'department_name',
        'status',
    ];

    // Relationship with guest
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    // Relationship with department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}