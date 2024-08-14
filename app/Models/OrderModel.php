<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    protected $table = 't_orders';

    protected $fillable = [
        'client_id',
        'order_id',
        'order_date',
        'amount',
        'log_date',
        'log_user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'client_id'); 
    }

    public function order_items()
    {
        return $this->hasMany(OrderItemsModel::class,'orderID', 'order_id');
    }
}
