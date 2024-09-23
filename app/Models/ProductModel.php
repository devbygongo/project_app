<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = "t_products";
    protected $fillable = [
        // 'sku',
        'product_code',
        'product_name',
        'product_image',
        'name_in_hindi',
        'name_in_telugu',
        'brand',
        'category',
        'sub_category',
        'type',
        'machine_part_no',
        'basic',
        'gst',
        'mark_up',
    ];

    public function transactions()
    {
        // return $this->hasMany(CartModel::class, 'products_id', 'product_code');
        return $this->hasMany(CartModel::class, 'product_code', 'product_code');
    }

    // Define the relationship: A product belongs to a category using the 'category' field
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category', 'name');
    }

    // Define the relationship: A product belongs to a sub_category using the 'category' field
    public function sub_category()
    {
        return $this->belongsTo(CategoryModel::class, 'sub_category', 'name');
    }
}
