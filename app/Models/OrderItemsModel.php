<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;

class OrderItemsModel extends Model
{
    use HasFactory;

    protected $table = 't_order_items';

    protected $fillable = [
        // 'orderID',
        'order_id',
        // 'item',
        'product_code',
        'product_name',
        'rate',
        // 'discount',
        'quantity',
        // 'line_total',
        'total',
        'remarks',
        'type',
    ];

    public function get_orders()
    {
        // return $this->belongsTo(OrderModel::class, 'orderID', 'order_id'); 
        return $this->belongsTo(OrderModel::class, 'order_id', 'id'); 
    }

    // Define the relationship to the Product model
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_code', 'product_code');
    }
}
