<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCartModel extends Model
{
    use HasFactory;

    protected $table = "t_stock_cart";
    protected $fillable = [
        'user_id',
        'product_code',
        'product_name',
        'quantity',
        'godown_id',
        'type',
    ];
}
