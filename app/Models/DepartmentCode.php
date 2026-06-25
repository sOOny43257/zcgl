<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentCode extends Model
{
    protected $fillable = ['type', 'code', 'name'];

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
