<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'user_id',
        'user_name',
        'field',
        'field_label',
        'old_value',
        'new_value',
        'reference_no',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
