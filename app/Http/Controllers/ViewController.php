<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

use App\Models\CounterModel;

class ViewController extends Controller
{
    //
    public function product()
    {
        // $get_product_details = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst','mark_up')->get();
        $get_product_details = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst')->get();
        

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
            // $get_products = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst','mark_up')
            // ->skip($offset)
            // ->take($limit)
            // ->get();
            $get_products = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst')
            ->skip($offset)
            ->take($limit)
            ->get();
        }
        else {
            $offset = 0;
            $limit = 10; // Number of records to fetch per batch
            // $get_products = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst','mark_up')
            // ->where('product_name', 'like', "%{$search}%")
            // ->skip($offset)
            // ->take($limit)
            // ->get();
            $get_products = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst')
            ->where('product_name', 'like', "%{$search}%")
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
        $get_categories = ProductModel::select('category')->distinct()->get();
        
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
        $get_subcategories = ProductModel::select('sub_category')->where('category',$category)->get();
        
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
        // $get_user_orders = OrderModel::where('client_id', $id)->get();
        $get_user_orders = OrderModel::where('user_id', $id)->get();
        

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
        // $get_items_for_orders = OrderItemsModel::where('orderID', $id)->get();
        $get_items_for_orders = OrderItemsModel::where('order_id', $id)->get();
        

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

    public function cart()
    {
        // Retrieve all records with their associated user and product data
        $get_all_cart_records = CartModel::with(['get_users', 'get_products'])->get();
        

        // Transform the data if needed
        $formattedData = $data->map(function ($item) {
            return [
                'id' => $item->id, // Adjust as necessary
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name, // Adjust fields as necessary
                ] : null,
                'product' => $item->product ? [
                    'product_code' => $item->product->product_code,
                    'name' => $item->product->name, // Adjust fields as necessary
                ] : null,
            ];
        });
        if (isset($formattedData)) {
            return response()->json([
                'message' => 'Fetch all recods successfully!',
                'data' => $formattedData
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed fetch records successfully!',
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

    public function counter()
    {
        $get_counter_records = CounterModel::all();
        
        if (isset($get_counter_records)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_counter_records
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }
    // return blade file
    
    public function login_view()
    {
        return view('login');
    }

    public function user_view()
    {
        return view('view_user');
    }
}