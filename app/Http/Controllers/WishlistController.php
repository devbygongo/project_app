<?php

namespace App\Http\Controllers;

use App\Models\WishlistModel;
use App\Models\OrderModel;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function saveToWishlist($user_id, $item)
    {
        // Fetch the actual order date from the Order model
        $order = OrderModel::find($item['order_id']);
        $order_date = $order ? $order->order_date : now(); // Default to current date if
        
        WishlistModel::create([
            'user_id' => $user_id,
            'product_code' => $item['product_code'], // Assuming the product ID is passed in the item
            'qty' => $item['quantity'],
            'type' => $item['type'],
            'order_id' => $item['order_id'],
            'order_date' => $order_date, // Use the actual order date
            'remarks' => $item['removalRemarks'] ?? '',
            'status' => 'pending',
        ]);
    }
}

?>
