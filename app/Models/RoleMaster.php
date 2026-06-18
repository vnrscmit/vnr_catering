<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleMaster extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'short_code', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
