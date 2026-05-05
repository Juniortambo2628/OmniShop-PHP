<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'prod_id', 'code', 'name', 'category_id', 'price', 
        'price_display', 'is_poa', 'is_active', 'dimensions', 
        'colors_json', 'is_override'
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'is_active' => 'boolean',
        'is_poa' => 'boolean',
        'colors_json' => 'array',
    ];

    public function getColorsAttribute()
    {
        return $this->colors_json ?: [];
    }
}
