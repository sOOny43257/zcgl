<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'consumable_id', 'consumable_name', 'user_id', 'user_name',
        'action', 'description', 'old_stock', 'new_stock',
        'reference_type', 'reference_id', 'created_at',
    ];

    protected $casts = [
        'old_stock' => 'integer',
        'new_stock' => 'integer',
        'created_at' => 'datetime',
    ];

    public function consumable()
    {
        return $this->belongsTo(Consumable::class, 'consumable_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
