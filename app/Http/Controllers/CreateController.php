<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

use Illuminate\Support\Facades\Auth;

use Hash;

class CreateController extends Controller
{
    //

    public function user(Request $request)
    {
        $request->validate([
            'email' => 'required|unique:users,email',
            'mobile' => ['required', 'string', 'size:13', 'unique:users'],
            'name' => 'required',
            'password' => 'required',
            'role' => 'required',
            // 'category_discount' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
            'pincode' => 'required',
        ]);

            $create_user = User::create([
                'name' => $request->input('name'),
                'password' => bcrypt($request->input('password')),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'role' => $request->input('role'),
                'address_line_1' => $request->input('address_line_1'),
                'address_line_2' => $request->input('address_line_2'),
                'city' => $request->input('city'),
                'pincode' => $request->input('pincode'),
                'gstin' => $request->input('gstin'),
                'state' => $request->input('state'),
                'country' => $request->input('country'),
                // 'category_discount' => $request->input('category_discount'),
            ]);


        if (isset($create_user)) {
            return response()->json([
                'message' => 'User created successfully!',
                'data' => $create_user
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create successfully!',
                'data' => $create_user
            ], 400);
        }    
    }

    public function login(Request $request, $otp = null)
    {
        if($otp)
        {
            $request->validate([
                'mobile' => ['required', 'string', 'size:13'],
            ]);

            $otpRecord = User::select('otp', 'expires_at')
            ->where('mobile', $request->mobile)
            ->first();
            
            // Validate OTP and expiry
            if (!$otpRecord || $otpRecord->otp != $otp) {
                return response()->json(['message' => 'Invalid OTP.'], 400);
            }

            if ($otpRecord->expires_at < now()) {
                return response()->json(['message' => 'OTP has expired.'], 400);
            } 

            else {
                // Remove OTP record after successful validation
                User::select('otp')->where('mobile', $request->mobile)->update(['otp' => null, 'expires_at' => null]);

                // Retrieve the user
                $user = User::where('mobile', $request->mobile)->first();

                // Generate a Sanctum token
                $token = $user->createToken('API TOKEN')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'message' => 'User login successfully.',
            ], 200);
            }
        }
        else
        {
            $request->validate([
                // 'email' => 'required|email',
                'mobile' => 'required',
                'password' => 'required',
            ]);
    
            if(Auth::attempt(['mobile' => $request->mobile, 'password' => $request->password])){ 
                $user = Auth::user(); 
    
                // Check the user's role
                if ($user->role !== 'admin' && $user->role !== 'user') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized.',
                        'errors' => ['error' => 'You do not have access to this section.'],
                    ], 403);
                }
    
                // Generate a Sanctum token
                $token = $user->createToken('API TOKEN')->plainTextToken;
       
                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        // 'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                    ],
                    'message' => 'User login successfully.',
                ], 200);
            } 
            else{ 
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                    'errors' => ['error' => 'Unauthorized'],
                ], 401);
            } 
        }
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function webLogout(Request $request)
    {
        // Log the user out of the session
        Auth::logout();

        // Invalidate the user's session
        $request->session()->invalidate();

        // Regenerate the session token to prevent CSRF attacks
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
    
    public function product(Request $request)
    {
        $request->validate([
            'sku' => 'required|unique:t_products,sku',
            'product_code' => 'required|unique:t_products,product_code',
            'product_name' => 'required',
            'product_image'=> 'required',
            'basic'=>'required',
            'gst'=>'required',
            'mark_up'=>'required',
        ]);

        if($request->hasFile('product_image'))
        {
            $file = $request->file('product_image');
            $filename = time().'_'. $file->getClientOriginalName();
            $path = $file->storeAs('uploads', $filename, 'public');
            $fileUrl = ('storage/uploads/' . $filename); 
            $get_file_name = $filename;


            $create_order = ProductModel::create([
                'sku' => $request->input('sku'),
                'product_code' => $request->input('product_code'),
                'product_name' => $request->input('product_name'),
                'category' => $request->input('category'),
                'sub_category' => $request->input('sub_category'),
                'product_image' => $fileUrl,
                'basic' => $request->input('basic'),
                'gst' => $request->input('gst'),
                'mark_up' => $request->input('mark_up'),
            ]);
        }


        if (isset($create_order)) {
            return response()->json([
                'message' => 'Customer created successfully!',
                'data' => $create_order
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed created successfully!',
                'data' => $create_order
            ], 400);
        }    
    }

    public function orders(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'order_id' => 'required',
            'order_date' => 'required',
            'amount' => 'required',
            'log_date' => 'required',
            'log_user' => 'required',
        ]);

            $create_order = OrderModel::create([
                'client_id' => $request->input('client_id'),
                'order_id' => $request->input('order_id'),
                'order_date' => $request->input('order_date'),
                'amount' => $request->input('amount'),
                'log_date' => $request->input('log_date'),
                'log_user' => $request->input('log_user'),
            ]);


        if (isset($create_order)) {
            return response()->json([
                'message' => 'Order created successfully!',
                'data' => $create_order
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create order successfully!',
                'data' => $create_order
            ], 400);
        }    
    }

    public function orders_items(Request $request)
    {
        $request->validate([
            'orderID' => 'required',
            'item' => 'required',
            'rate' => 'required',
            'discount' => 'required',
            'line_total' => 'required',
        ]);

            $create_order_items = OrderItemsModel::create([
                'orderID' => $request->input('orderID'),
                'item' => $request->input('item'),
                'rate' => $request->input('rate'),
                'discount' => $request->input('discount'),
                'line_total' => $request->input('line_total'),
            ]);


        if (isset($create_order_items)) {
            return response()->json([
                'message' => 'Order Items created successfully!',
                'data' => $create_order_items
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create order items successfully!'
            ], 400);
        }    
    }

    public function cart(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'products_id' => 'required',
            'rate' => 'required',
            'quantity' => 'required',
            'amount' => 'required',
            'type' => 'required',
        ]);

            $create_cart = CartModel::create([
                'user_id' => $request->input('user_id'),
                'products_id' => $request->input('products_id'),
                'rate' => $request->input('rate'),
                'quantity' => $request->input('quantity'),
                'amount' => $request->input('amount'),
                'type' => $request->input('type'),
            ]);


        if (isset($create_cart)) {
            return response()->json([
                'message' => 'Cart created successfully!',
                'data' => $create_cart
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create order successfully!'
            ], 400);
        }    
    }

}