<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = "t_products";
    protected $fillable = [
        'SKU',
        'Product_Code',
        'Product_Name',
        'Product_Image',
        'Category',
        'Sub_Category',
        'basic',
        'gst',
        'mark_up',
    ];

    public function transactions()
    {
        return $this->hasMany(CartModel::class, 'products_id', 'Product_Code');
    }
}
