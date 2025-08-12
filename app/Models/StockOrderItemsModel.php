<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOrderItemsModel extends Model
{
    use HasFactory;

    protected $table = "t_stock_order_items";
    protected $fillable = [
        'stock_order_id',
        'product_code',
        'product_name',
        'godown_id',
        'quantity',
        'type',
        'size',
    ];

    // Define the relationship to the Product model
    public function stock_product()
    {
        return $this->belongsTo(ProductModel::class, 'product_code', 'product_code');
    }

    public function godown()
    {
        return $this->belongsTo(GodownModel::class, 'godown_id', 'id'); // Assuming 'godown' is a foreign key in t_stock_orders referencing id in t_godown
    }

    // Relationship with StockOrderModel
    public function stockOrder()
    {
        return $this->belongsTo(StockOrdersModel::class, 'stock_order_id', 'id');
    }
}
