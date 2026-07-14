<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyParameterLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_parameter_id',
        'user_id',
        'location_id',
        'member_rate',
        'non_member_rate',
        'guest_rate',
        'attendance_out_time',
        'lunch_out_time',
        'max_day_show',
        'active_from_date',
        'active_till_date',
        'status',
        'action',
    ];

    public function companyParameter()
    {
        return $this->belongsTo(CompanyParameter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}