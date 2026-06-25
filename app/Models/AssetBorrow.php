<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetBorrow extends Model
{
    protected $fillable = [
        'asset_id', 'order_no', 'borrower', 'department',
        'borrow_date', 'expected_return_date', 'return_date',
        'previous_status', 'remarks',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'expected_return_date' => 'date',
        'return_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
