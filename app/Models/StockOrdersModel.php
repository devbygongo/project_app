<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOrdersModel extends Model
{
    use HasFactory;

    protected $table = "t_stock_orders";
    protected $fillable = [
        'order_id',
        'user_id',
        'order_date',
        'type',
        'pdf',
        'remarks',
    ];
}
