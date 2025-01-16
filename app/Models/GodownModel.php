<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GodownModel extends Model
{
    use HasFactory;

    protected $table = "t_godown";
    protected $fillable = [
        'name',
        'description',
    ];

    // Relationship with StockOrderItemsModel
    public function stockOrderItems()
    {
        return $this->hasMany(StockOrderItemsModel::class, 'godown_id', 'id');
    }

    // Relationship with StockCartModel
    public function stockCartItems()
    {
        return $this->hasMany(StockCartModel::class, 'godown_id', 'id');
    }
}
