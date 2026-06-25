<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'ip', 'url', 'method',
        'user_agent', 'browser', 'platform', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];
}
