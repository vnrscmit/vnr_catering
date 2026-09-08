<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ledger extends Model
{
    use HasFactory;

    protected $table = 'ledgers';

    protected $fillable = [
        'location_id',
        'user_id',
        'calendar_id',
        'bill_id',
        'date',
        'transaction',
        'due',
        'paid',
        'balance',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due' => 'integer',
        'paid' => 'integer',
        'balance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(DayStatus::class, 'calendar_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }


    public function getFormattedDueAttribute()
    {
        return  number_format($this->due);
    }

    public function getFormattedPaidAttribute()
    {
        return  number_format($this->paid);
    }

    public function getFormattedBalanceAttribute()
    {
        return  number_format($this->balance);
    }
}
