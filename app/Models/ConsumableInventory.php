<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableInventory extends Model
{
    protected $fillable = [
        'inventory_no', 'inventory_date', 'operator_id',
        'operator_name', 'status', 'remarks',
    ];

    protected $casts = [
        'inventory_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ConsumableInventoryItem::class, 'inventory_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public static function generateInventoryNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'HC-PD-' . $today . '-';
        $last = self::where('inventory_no', 'like', $prefix . '%')
            ->orderByDesc('inventory_no')
            ->value('inventory_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
