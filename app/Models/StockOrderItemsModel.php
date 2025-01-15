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
        'quantity',
        'type',
    ];

    // Define the relationship to the Product model
    public function stock_product()
    {
        return $this->belongsTo(ProductModel::class, 'product_code', 'product_code');
    }
}
