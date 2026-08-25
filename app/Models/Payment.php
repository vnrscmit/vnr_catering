<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'bill_id',
        'bill_detail_id',
        'user_id',
        'payable_amount',
        'receive_amount',
        'balance_amount',
        'amount',
        'status',
        'payment_date',
        'payment_time',
    ];

    protected $casts = [
        'payable_amount' => 'integer',
        'receive_amount' => 'integer',
        'balance_amount' => 'integer',
        'amount' => 'integer',
        'payment_date' => 'date',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function billDetail()
    {
        return $this->belongsTo(BillDetail::class, 'bill_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getPayableAmountAttribute($value)
    {
        return (int) $value;
    }

    public function getReceiveAmountAttribute($value)
    {
        return (int) $value;
    }

    public function getBalanceAmountAttribute($value)
    {
        return (int) $value;
    }

    public function getAmountAttribute($value)
    {
        return (int) $value;
    }

    // Mutators
    public function setPayableAmountAttribute($value)
    {
        $this->attributes['payable_amount'] = (int) $value;
    }

    public function setReceiveAmountAttribute($value)
    {
        $this->attributes['receive_amount'] = (int) $value;
    }

    public function setBalanceAmountAttribute($value)
    {
        $this->attributes['balance_amount'] = (int) $value;
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = (int) $value;
    }

    // Helper Methods
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPartial()
    {
        return $this->status === 'partial';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function getBalance()
    {
        return $this->balance_amount;
    }

    public function getTotalPaid()
    {
        return $this->receive_amount;
    }
}