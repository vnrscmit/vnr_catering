<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'short_code', 'status', 'location_id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function getByLocation($departmentId)
    {
        $alreadylinkedData = DepartmentLocation::select(
            'department_locations.id',
            'department_locations.department_id',
            'department_locations.location_id',
            'departments.name as department_name',
            'locations.name as location_name'
        )
            ->join('departments', 'departments.id', '=', 'department_locations.department_id')
            ->join('locations', 'locations.id', '=', 'department_locations.location_id')
            ->where('department_locations.department_id', $departmentId)
            ->get();

            return $alreadylinkedData;
    }
}
