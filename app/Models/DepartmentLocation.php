<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentLocation extends Model
{
    protected $fillable = [
        'department_id',
        'location_id',
        'status',
    ];
}
