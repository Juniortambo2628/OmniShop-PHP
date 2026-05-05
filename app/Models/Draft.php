<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    protected $fillable = ['user_id', 'target_type', 'target_id', 'content'];

    protected $casts = [
        'content' => 'array',
    ];
}
