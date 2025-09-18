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
        'order_by',
        'product_code',
        'product_name',
        'product_image',
        'extra_images',
        'name_in_hindi',
        'name_in_telugu',
        'brand',
        'category',
        'sub_category',
        'type',
        'hsn',
        'tax',
        'size',
        'machine_part_no',
        'purchase',
        'basic',
        'gst',
        'special_basic',
        'special_gst',
        'outstation_basic',
        'outstation_gst',
        'aakhambati_gst',
        // 'mark_up',
        'guest_price',
        're_order_level',
        'out_of_stock',
        'yet_to_launch',
        'video_link'
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
