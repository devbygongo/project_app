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
        't_order_id',
        'pdf',
        'remarks',
    ];

    public function items()
    {
        return $this->hasMany(StockOrderItemsModel::class, 'stock_order_id');
    }

    // Relationship with UserModel
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
