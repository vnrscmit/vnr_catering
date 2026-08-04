<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
    protected $fillable = [

        'bill_id',
        'type',
        'user_id',
        'user_diets',
        'rate_per_diet',
        'bill_amount',
        'status'

    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
