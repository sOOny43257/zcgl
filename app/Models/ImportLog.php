<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'type',
        'file_name',
        'total_rows',
        'inserted',
        'updated',
        'skipped',
        'changed_details',
        'errors',
        'transfer_order_id',
        'operator_id',
        'operator_name',
        'operator',
        'import_reason',
        'is_cancelled',
        'cancelled_at',
    ];

    protected $casts = [
        'changed_details' => 'array',
        'errors' => 'array',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class, 'transfer_order_id');
    }
}
