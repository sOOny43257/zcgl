<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetIntake extends Model
{
    protected $fillable = [
        'order_no', 'intake_date', 'supplier', 'purchase_order_no',
        'total_amount', 'operator', 'approver', 'status',
        'draft_data', 'remarks', 'description', 'attachments',
    ];

    protected $casts = [
        'draft_data' => 'array',
        'attachments' => 'array',
        'total_amount' => 'decimal:2',
        'intake_date' => 'date',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'intake_id');
    }

    public static function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'RK-' . $today . '-';
        $last = self::where('order_no', 'like', $prefix . '%')
            ->orderBy('order_no', 'desc')
            ->value('order_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
