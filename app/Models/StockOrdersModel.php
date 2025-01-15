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
        'pdf',
        'remarks',
    ];

    public function items()
    {
        return $this->hasMany(StockOrderItemsModel::class, 'stock_order_id');
    }

    public function godown()
    {
        return $this->belongsTo(GodownModel::class, 'godown_id', 'id'); // Assuming 'godown' is a foreign key in t_stock_orders referencing id in t_godown
    }

}
