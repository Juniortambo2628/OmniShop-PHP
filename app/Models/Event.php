<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'dates',
        'venue',
        'logo',
        'contact_email',
        'catalog_password_default',
        'order_prefix',
        'deadlines',
    ];

    protected $casts = [
        'deadlines' => 'array',
    ];
}
