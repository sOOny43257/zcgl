<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableIntakeItem extends Model
{
    protected $fillable = [
        'intake_order_id', 'consumable_id', 'quantity',
        'unit_price', 'subtotal', 'remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function intakeOrder()
    {
        return $this->belongsTo(ConsumableIntakeOrder::class, 'intake_order_id');
    }

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
}
