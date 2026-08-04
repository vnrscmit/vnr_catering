<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [

        'type',
        'user_id',
        'generate_date',
        'generate_month',
        'calendar_id',
        'bill_no',
        'bill_date',
        'total_diets',
        'individual_set_diet',
        'president_diet',
        'guest_diet',
        'net_chargeable_diet',
        'total_expenses',
        'guest_expenses',
        'individual_expenses',
        'net_monthly_expenses',
        'per_diet_calculation',
        'status'
    ];

    public function details()
    {
        return $this->hasMany(BillDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
