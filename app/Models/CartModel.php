<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    use HasFactory;

    protected $table = 't_cart';

    protected $fillable = [
        'user_id',
        'products_id',
        'rate',
        'quantity',
        'amount',
        'type',
    ];

    public function get_users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); 
    }

    public function get_products()
    {
        return $this->belongsTo(ProductModel::class, 'products_id', 'product_code'); 
    }
}
