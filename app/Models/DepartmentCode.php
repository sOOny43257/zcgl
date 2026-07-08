<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DepartmentCode extends Model
{
    protected $fillable = ['type', 'code', 'name'];

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Resolve a department code or raw name to its display name.
     * Handles both stored-as-code and stored-as-name scenarios.
     */
    public static function resolveName(string $type, ?string $value): string
    {
        if (!$value) return '-';

        $map = Cache::remember("dept_map_{$type}", 3600, function () use ($type) {
            return static::type($type)->pluck('name', 'code')->toArray();
        });

        // Direct code match
        if (isset($map[$value])) return $map[$value];

        // Value is already a name (legacy data)
        if (in_array($value, $map)) return $value;

        return $value;
    }

    /** Clear cached maps after updates. */
    protected static function booted()
    {
        static::saved(fn () => Cache::forget('dept_map_department'));
        static::deleted(fn () => Cache::forget('dept_map_department'));
    }
}
