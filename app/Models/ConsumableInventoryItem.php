<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableInventoryItem extends Model
{
    protected $fillable = [
        'inventory_id', 'consumable_id', 'book_quantity',
        'actual_quantity', 'difference', 'reason', 'adjusted',
    ];

    protected $casts = [
        'book_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'adjusted' => 'boolean',
    ];

    public function inventory()
    {
        return $this->belongsTo(ConsumableInventory::class, 'inventory_id');
    }

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
}
