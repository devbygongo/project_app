<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

class ViewController extends Controller
{
    //
    public function product()
    {
        $get_product_details = ProductModel::select('SKU','Product_Code','Product_Name','Category','Sub_Category','Product_Image','basic','gst','mark_up')->get();
        

        if (isset($get_product_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_product_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function get_product($search = null)
    {
        if(!isset($search))
        {
            $offset = 0;
            $limit = 10; // Number of records to fetch per batch
            $get_products = ProductModel::select('SKU','Product_Code','Product_Name','Category','Sub_Category','Product_Image','basic','gst','mark_up')
            ->skip($offset)
            ->take($limit)
            ->get();
        }
        else {
            $offset = 0;
            $limit = 10; // Number of records to fetch per batch
            $get_products = ProductModel::select('SKU','Product_Code','Product_Name','Category','Sub_Category','Product_Image','basic','gst','mark_up')
            ->where('Product_Name', 'like', "%{$search}%")
            ->skip($offset)
            ->take($limit)
            ->get();
        }
        

        if (isset($get_products)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_products
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function categories()
    {
        $get_categories = ProductModel::select('Category')->distinct()->get();
        
        if (isset($get_categories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_categories
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function sub_categories($category)
    {
        $get_subcategories = ProductModel::select('Sub_Category')->where('Category',$category)->get();
        
        if (isset($get_subcategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_subcategories
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function user()
    {
        $get_user_details = User::select('name','email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')->get();
        

        if (isset($get_user_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function user_details()
    {
        $get_user_id = Auth::id();
        
        $get_user_details = User::select('id','name','email','mobile','address_line_1','address_line_2','city','pincode','gstin','state','country')->where('id', $get_user_id)->get();
        

        if (isset($get_user_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders()
    {
        $get_all_orders = OrderModel::with('user')->get();
        

        if (isset($get_all_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_orders
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders_user_id($id)
    {
        $get_user_orders = OrderModel::where('client_id', $id)->get();
        

        if($get_user_orders->isEmpty()) {
            return response()->json([
                'message' => 'Sorry, no data available!',
            ], 400);
        }

        else {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_orders
            ], 201);
        }    
    }

    public function order_items()
    {
        $get_all_order_items = OrderItemsModel::with('get_orders')->get();
        

        if (isset($get_all_order_items)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_order_items
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders_items_order_id($id)
    {
        $get_items_for_orders = OrderItemsModel::where('orderID', $id)->get();
        

        if (isset($get_items_for_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_orders
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function cart_user($id)
    {
        $get_items_for_user = CartModel::where('user_id', $id)->get();
        

        if (isset($get_items_for_user)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_user
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }
}
