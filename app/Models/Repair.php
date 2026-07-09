<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    protected $fillable = [
        'order_no', 'asset_id', 'repair_date', 'fault_category',
        'fault_description', 'repair_method', 'vendor', 'cost',
        'expected_completion_date', 'actual_completion_date',
        'attachments', 'operator', 'status',
        'previous_asset_status', 'remarks',
    ];

    protected $casts = [
        'repair_date' => 'date',
        'expected_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'cost' => 'decimal:2',
        'attachments' => 'array',
    ];

    const STATUSES = [
        'draft' => '草稿',
        'submitted' => '已提交',
        'in_progress' => '维修中',
        'completed' => '已完成',
        'cancelled' => '已作废',
    ];

    const FAULT_CATEGORIES = ['硬件', '软件', '网络', '其他'];
    const REPAIR_METHODS = ['内部', '外包'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public static function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'WX-' . $today . '-';
        $last = self::where('order_no', 'like', $prefix . '%')
            ->orderBy('order_no', 'desc')
            ->value('order_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
