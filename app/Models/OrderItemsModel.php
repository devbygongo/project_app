<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemsModel extends Model
{
    use HasFactory;

    protected $table = 't_order_items';

    protected $fillable = [
        'orderID',
        'item',
        'rate',
        'discount',
        'line_total',
    ];

    public function get_orders()
    {
        return $this->belongsTo(OrderModel::class, 'orderID', 'order_id'); 
    }
}
