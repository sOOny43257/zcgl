<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSubmission extends Model
{
    protected $fillable = [
        'name', 'department', 'room', 'ip', 'mac', 'sn',
        'status', 'errors', 'suggestions', 'submit_log',
    ];

    protected $casts = [
        'errors' => 'array',
        'suggestions' => 'array',
    ];
}
