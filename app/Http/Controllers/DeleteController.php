<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\CartModel;

use App\Models\User;

use App\Models\StockCartModel;

use App\Models\StockOrdersModel;

use App\Models\StockOrderItemsModel;

use App\Models\SpecialRateModel;

use App\Models\JobCardModel;

class DeleteController extends Controller
{
    //Delete Cart 
    public function cart($id)
    {
		
        $get_cart_records = CartModel::find($id);
        
        if (!$get_cart_records == null) 
        {
            $delete_cart_records = $get_cart_records->delete();

            if ($delete_cart_records == true ) {
                return response()->json([
                    'message' => 'Cart deleted successfully!',
                    'data' => $delete_cart_records
                ], 201);
            }
            else{
                return response()->json([
                    'message' => 'Failed to delete successfully!',
                    'data' => $delete_cart_records
                ], 400);
            }
        }
        else{
            return response()->json([
                'message' => 'sorry, can\'t fetch the record!',
            ], 500);
        }
    }

    // delete user
    public function user($id = null)
    {
        $getRole = (Auth::user())->role;

        if ($getRole == 'user') {
            $id = Auth::id();
        }

        // Fetch the record by ID
        // Check if the record exists
        $get_user = User::find($id);

        if (!$get_user) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, User not found!',
            ], 200);
        }
               
        else{
            $delete_user_records = $get_user->delete();

            if ($delete_user_records == true ) {
                return response()->json([
                    'success' => true,
                    'message' => 'User Deleted Successfully.',
                ], 200);
            }
            else{
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, Failed to delete user!',
                ], 200);
            }
        }
    }

    // Delete operation
    public function stock_cart_destroy($id)
    {
        $stockCartItem = StockCartModel::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$stockCartItem) {
            return response()->json(['message' => 'Stock cart item not found.'], 404);
        }

        $stockCartItem->delete();

        return response()->json([
            'message' => 'Stock cart item deleted successfully.',
        ], 200);
    }

    public function deleteStockOrder($orderId)
    {
        try {
            // Fetch the stock order by order_id
            $stockOrder = StockOrdersModel::where('id', $orderId)->first();

            if (!$stockOrder) {
                return response()->json([
                    'message' => 'Stock order not found.',
                    'status' => 'false',
                ], 404);
            }

            // Check if the logged-in user is authorized to delete the stock order
            $userId = Auth::id();
            if ($stockOrder->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized to delete this stock order.',
                    'status' => 'false',
                ], 403);
            }

            // Begin a database transaction
            \DB::beginTransaction();

            // Delete the associated stock order items
            StockOrderItemsModel::where('stock_order_id', $stockOrder->id)->delete();

            // Delete the stock order
            $stockOrder->delete();

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'message' => 'Stock order and associated items deleted successfully.',
                'order_id' => $stockOrder->order_id,
                'status' => 'true',
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            \DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while deleting the stock order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    /**
     * DELETE
     */
    public function deleteSpecialRate($id)
    {
        try {
            $special = SpecialRateModel::find($id);
            if (!$special) {
                return response()->json([
                    'success' => false,
                    'message' => 'Special rate record not found.'
                ], 404);
            }

            $special->delete();

            return response()->json([
                'success' => true,
                'message' => 'Special rate deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            Log::error('SpecialRate Delete Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting special rate.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}