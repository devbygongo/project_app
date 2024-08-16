<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CartModel;

class DeleteController extends Controller
{
    //
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
}
