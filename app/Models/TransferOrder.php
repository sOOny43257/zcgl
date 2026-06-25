<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'asset_id', 'log_ids',
        'from_dept', 'to_dept', 'from_user', 'to_user',
        'operator', 'status', 'reason', 'draft_data',
        'is_cancelled', 'cancelled_at',
    ];

    protected $casts = [
        'log_ids' => 'array',
        'draft_data' => 'array',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
