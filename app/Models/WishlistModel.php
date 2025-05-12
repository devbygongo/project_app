<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistModel extends Model
{
    use HasFactory;

    protected $table = 't_wishlist';

    protected $fillable = [
        'user_id',
        'product_id',
        'qty',
        'type',
        'order_id',
        'order_date',
        'remarks',
        'status'
    ];
}

?>
