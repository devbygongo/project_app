<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CartModel;

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
    public function user(Request $request)
    {
        // Fetch the record by ID
        $get_user = User::where('mobile', $request->input('mobile'))->first();

        // Check if the record exists
        if (!$get_user) {
            return response()->json([
                'message' => 'Sorry, User not found!',
            ], 404);
        }
		        
        else{
            $delete_user_records = $get_user->delete();

            if ($delete_user_records == true ) {
                return response()->json([
                    'message' => 'User deleted successfully!',
                ], 204);
            }
            else{
                return response()->json([
                    'message' => 'Sorry, Failed to delete user',
                ], 404);
            }
        }
    }
}