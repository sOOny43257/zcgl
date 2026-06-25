<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferOrder extends Model
{
    protected $fillable = [
        'order_no', 'asset_id', 'log_ids',
        'from_dept', 'to_dept', 'from_user', 'to_user',
        'operator', 'is_cancelled', 'cancelled_at',
    ];

    protected $casts = [
        'log_ids' => 'array',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
