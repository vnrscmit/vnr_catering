<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMaster extends Model
{
    protected $table = 'event_masters';

    protected $fillable = [
        'seq_no',
        'name',
        'short_code',
        'status',
    ];
}
