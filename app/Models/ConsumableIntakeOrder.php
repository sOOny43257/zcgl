<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableIntakeOrder extends Model
{
    protected $fillable = [
        'order_no', 'intake_date', 'supplier_code',
        'operator_id', 'operator_name', 'status', 'remarks',
    ];

    protected $casts = [
        'intake_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ConsumableIntakeItem::class, 'intake_order_id');
    }

    public function supplierName(): string
    {
        return $this->supplier_code
            ? (DepartmentCode::where('type', 'supplier')->where('code', $this->supplier_code)->value('name') ?? $this->supplier_code)
            : '-';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public static function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'HC-RK-' . $today . '-';
        $last = self::where('order_no', 'like', $prefix . '%')
            ->orderByDesc('order_no')
            ->value('order_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
