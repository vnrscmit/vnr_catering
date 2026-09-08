<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAmount extends Model
{
    use HasFactory;

    protected $table = 'security_amounts';

    protected $fillable = [
        'transaction_id',
        'date',
        'user_id',
        'amount',
        'total_amount',
        'mode_of_collection',
        'status',
        'created_by',
    ];



    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
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

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '₹ ' . number_format($this->amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return '₹ ' . number_format($this->total_amount, 2);
    }

    public function getStatusTextAttribute()
    {
        return $this->status == 1 ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->status == 1
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Inactive</span>';
    }

    // Helper Methods
    public static function getTotalSecurityByUser($userId)
    {
        return self::where('user_id', $userId)->sum('amount');
    }

    public static function getLatestSecurityByUser($userId)
    {
        $totalAmount = self::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->value('total_amount');

        // If no value found or value is 0, get from users table
        if (empty($totalAmount) || $totalAmount == 0) {
            $user = \App\Models\User::find($userId);
            return $user ? $user->security_amount ?? 0 : 0;
        }

        return $totalAmount;
    }

    // Boot method for auto-calculations
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-set created_by if not provided
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::created(function ($model) {
            // Update total_amount after creation
            $total = self::where('user_id', $model->user_id)->sum('amount');
            $model->total_amount = $total;
            $model->saveQuietly();
        });

        static::updated(function ($model) {
            // Update total_amount after update
            $total = self::where('user_id', $model->user_id)->sum('amount');
            $model->total_amount = $total;
            $model->saveQuietly();
        });
    }
}
