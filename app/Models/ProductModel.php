<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = "t_products";
    protected $fillable = [
        'sku',
        'product_code',
        'Product_Name',
        'product_image',
        'category',
        'sub_category',
        'basic',
        'gst',
        'mark_up',
    ];

    public function transactions()
    {
        return $this->hasMany(CartModel::class, 'products_id', 'product_code');
    }
}
