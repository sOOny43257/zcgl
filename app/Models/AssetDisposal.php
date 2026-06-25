<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    protected $fillable = [
        'order_no', 'disposal_date', 'disposal_method', 'reason',
        'operator', 'approver', 'status', 'draft_data', 'remarks',
    ];

    protected $casts = [
        'draft_data' => 'array',
        'disposal_date' => 'date',
    ];

    public function getAssetsAttribute()
    {
        $ids = $this->draft_data['asset_ids'] ?? [];
        if (empty($ids)) return collect();
        return Asset::whereIn('id', $ids)->get();
    }

    public static function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'BF-' . $today . '-';
        $last = self::where('order_no', 'like', $prefix . '%')
            ->orderBy('order_no', 'desc')
            ->value('order_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
