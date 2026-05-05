<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLimit extends Model
{
    protected $fillable = [
        'product_code', 'product_name', 'category_id', 'stock_limit'
    ];
}
