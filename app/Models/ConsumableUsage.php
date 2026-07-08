<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    protected $fillable = [
        'consumable_id', 'department_code', 'quantity',
        'usage_date', 'reason', 'operator_id', 'operator_name',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'usage_date' => 'date',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }

    public function departmentName(): string
    {
        return Asset::translateDept($this->department_code);
    }
}
