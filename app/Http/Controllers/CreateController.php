<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

use App\Models\CounterModel;

use App\Models\InvoiceModel;

use App\Models\InvoiceItemsModel;

use Illuminate\Support\Facades\Auth;

use Hash;

use Carbon\Carbon;

use App\Http\Controllers\InvoiceController;

use App\Utils\sendWhatsAppUtility;

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
                'email' => strtolower($request->input('email')),
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


            $templateParams = [
                'name' => 'ace_new_user_registered', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $create_user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_user->mobile,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_user->state,
                            ],
                        ],
                    ]
                ],
            ];
            
            $whatsAppUtility = new sendWhatsAppUtility();
            
            $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Register');
  
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
            
            if ($otpRecord) 
            {
                // Validate OTP and expiry
                if (!$otpRecord || $otpRecord->otp != $otp) {
                    return response()->json(['message' => 'Invalid OTP.'], 400);
                }

                if ($otpRecord->expires_at < now()) {
                    return response()->json(['message' => 'OTP has expired.'], 400);
                } 

                else 
                {
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

            else{ 
                return response()->json([
                    'success' => false,
                    'message' => 'User not register.',
                ], 401);
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
                // if ($user->role !== 'admin' && $user->role !== 'user') {
                if ($user->verified == '0') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized.',
                        'errors' => ['error' => 'You do not have access to this section.\nPlease Verify your account first'],
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
        // Check if the user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No user is authenticated.',
            ], 401); // 401 Unauthorized
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 204);
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
            // 'mark_up'=>'required',
        ]);

        if($request->hasFile('product_image'))
        {
            $file = $request->file('product_image');
            // $filename = time().'_'. $file->getClientOriginalName();
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('uploads/products', $filename, 'public');
            $fileUrl = ('storage/uploads/products' . $filename); 
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
                // 'mark_up' => $request->input('mark_up'),
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
        $get_user = Auth::User();

        if($get_user->role == 'user') {
            $userId = $get_user->id;
        }

        else 
        {
            $request->validate([
                // 'client_id' => 'required',
                'user_id' => 'required',
            ]);
            $userId = $request->input('user_id');
        }

        $create_order_basic = null;
        $create_order_gst = null;

        $get_basic_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate', 'type')
                                       ->where('user_id', $userId)
                                       ->where('type', 'basic')
                                       ->get();

        $get_counter_data = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order_basic')->get();

        if ($get_counter_data) 
        {
            $get_order_id = $get_counter_data[0]->prefix.$get_counter_data[0]->counter.$get_counter_data[0]->postfix;
    
            // for `basic` product
            if ((count($get_basic_product)) > 0) 
            {
                $product_basic_amount = 0;
                foreach ($get_basic_product as $basic_product) 
                {
                    $product_basic_amount += (($basic_product->amount) * ($basic_product->quantity));
                }
                
                $create_order_basic = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $product_basic_amount,
                    'type' => 'basic',
                ]);
                //order_table_id

                foreach ($get_basic_product as $basic_product) 
                {
                    // save every item in order_items with order_table_id
                    $create_order_items = OrderItemsModel::create([
                        'order_id' => $create_order_basic->id,
                        'product_code' => $basic_product->product_code,
                        'product_name' => $basic_product->product_name,
                        'rate' => $basic_product->rate,
                        'quantity' => $basic_product->quantity,
                        'total' => $product_basic_amount,
                        'type' => $basic_product->type,
                    ]);
                }
            }

        }

        $get_gst_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate', 'type')
                                      ->where('user_id', $userId)
                                      ->where('type', 'gst')
                                      ->get();

        $get_counter_data = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order_gst')->get();

        if ($get_counter_data) 
        {
            $get_order_id = $get_counter_data[0]->prefix.$get_counter_data[0]->counter.$get_counter_data[0]->postfix;

            // for `gst` product    
            if ((count($get_gst_product)) > 0) 
            {
                $product_gst_amount = 0;
                foreach ($get_gst_product as $gst_product) {
                    $product_gst_amount += (($gst_product->amount) * ($gst_product->quantity));
                }

                $create_order_gst = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $product_gst_amount,
                    'type' => 'gst',
                ]);

                 //order_table_id
                 foreach ($get_gst_product as $gst_product) {
                    // save every item in order_items with order_table_id
                    $create_order_items = OrderItemsModel::create([
                        'order_id' => $create_order_gst->id,
                        'product_code' => $gst_product->product_code,
                        'product_name' => $gst_product->product_name,
                        'rate' => $gst_product->rate,
                        'quantity' => $gst_product->quantity,
                        'total' => $product_gst_amount,
                        'type' => $gst_product->type,
                    ]);
                }
                
            }
        }

        if ($create_order_basic != null) {
            $update_cart = CounterModel::where('name', 'order_basic')
            ->update([
                'counter' => (($get_counter_data[0]->counter)+1),
            ]);
        }

        if($create_order_gst != null)
        {
            $update_cart = CounterModel::where('name', 'order_gst')
                                        ->update([
                                         'counter' => (($get_counter_data[0]->counter)+1),
            ]);
        }

        $data = [];

        // Check if data_basic exists and is not null, then add it to the array
        if(!empty($create_order_basic))
        {
            $data[] = $create_order_basic;
        }

        // Check if data_gst exists and is not null, then add it to the array
        if(!empty($create_order_gst))
        {
            $data[] = $create_order_gst;
        }

        // $get_remove_items = CartModel::where('user_id', $userId)->delete();

        if ($create_order_basic !== null || $create_order_gst !== null) {

            $generate_invoice = new InvoiceController();

             // This will store the invoices generated for display or further processing
            $invoices = [];

            // Check if $create_order_basic is not null and has an id
            if (!is_null($create_order_basic) && isset($create_order_basic->id)) 
            {
                // Generate invoice for $create_order_basic
                $invoices['basic'] = $generate_invoice->generateInvoice($create_order_basic->id);
            }

            // Check if $create_order_gst is not null and has an id
            if (!is_null($create_order_gst) && isset($create_order_gst->id)) 
            {
                // Generate invoice for $create_order_gst
                $invoices['gst'] = $generate_invoice->generateInvoice($create_order_gst->id);
            }

            // Add invoices to the $data array under a specific key
            $data['invoices'] = $invoices;

            return response()->json([
                'message' => 'Order created and Invoice generated successfully!',
                'data' => $data
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Sorry, failed to create order!',
                'data' => 'Error!'
            ], 400);
        }    
    }

    public function orders_items(Request $request)
    {
        $request->validate([
            // 'orderID' => 'required',
            'order_id' => 'required',
            // 'item' => 'required',
            'product_code' => 'required',
            'product_name' => 'required',
            'rate' => 'required',
            // 'discount' => 'required',
            'quantity' => 'required',
            // 'line_total' => 'required',
            'total' => 'required',
        ]);

            $create_order_items = OrderItemsModel::create([
                // 'orderID' => $request->input('orderID'),
                'order_id' => $request->input('order_id'),
                // 'item' => $request->input('item'),
                'product_code' => $request->input('product_code'),
                'product_name' => $request->input('product_name'),
                'rate' => $request->input('rate'),
                // 'discount' => $request->input('discount'),
                'quantity' => $request->input('quantity'),
                // 'line_total' => $request->input('line_total'),
                'total' => $request->input('total'),
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
        $get_user = Auth::User();

        if($get_user->role == 'admin')
        {
            $request->validate([
                // 'mobile' => 'required',
                'user_id' => 'required',
                // 'products_id' => 'required',
                'product_code' => 'required',
                'product_name' => 'required',
                'rate' => 'required',
                'quantity' => 'required',
                'amount' => 'required',
                'type' => 'required',
            ]);

            // $get_user_id = User::select('id')->where('mobile', $request->input('mobile'))->get();
    
            $create_cart = CartModel::updateOrCreate(
				[
					'user_id' => $request->input('user_id'),
					'product_code' => $request->input('product_code'),
				], 
				[
					'product_name' => $request->input('product_name'),
					'rate' => $request->input('rate'),
					'quantity' => $request->input('quantity'),
					'amount' => $request->input('amount'),
					'type' => $request->input('type'),
				]
			);

        }

    else {
        $request->validate([
            // 'user_id' => 'required',
            // 'products_id' => 'required',
            'product_code' => 'required',
            'product_name' => 'required',
            'rate' => 'required',
            'quantity' => 'required',
            'amount' => 'required',
            'type' => 'required',
        ]);

        /*$create_cart = CartModel::create([
            'user_id' => Auth::id(),
            // 'products_id' => $request->input('products_id'),
            'product_code' => $request->input('product_code'),
            'product_name' => $request->input('product_name'),
            'rate' => $request->input('rate'),
            'quantity' => $request->input('quantity'),
            'amount' => $request->input('amount'),
            'type' => $request->input('type'),
        ]);*/
		
		$create_cart = CartModel::updateOrCreate(
			[
				'user_id' => $request->input('user_id'),
				'product_code' => $request->input('product_code'),
			], 
			[
				'product_name' => $request->input('product_name'),
				'rate' => $request->input('rate'),
				'quantity' => $request->input('quantity'),
				'amount' => $request->input('amount'),
				'type' => $request->input('type'),
			]
		);

    }
        


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

    public function counter(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'counter' => 'required',
        ]);

            $create_counter = CounterModel::create([
                'name' => $request->input('name'),
                'prefix' => $request->input('prefix'),
                'counter' => $request->input('counter'),
                'postfix' => $request->input('postfix'),
            ]);


        if (isset($create_counter)) {
            return response()->json([
                'message' => 'Counter record created successfully!',
                'data' => $create_counter
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create counter record successfully!',
                'data' => $create_counter
            ], 400);
        }    
    }

    public function make_invoice(Request $request)
    {
        $create_invoice = InvoiceModel::create([
            'order_id' => $request->input('0.order_id'),
            'user_id' => $request->input('0.user_id'),
            'invoice_number' => $request->input('0.invoice_no'),
            'date' => $request->input('0.invoice_date'),
            'amount' => $request->input('0.amount'),
            'type' => $request->input('0.type'),
        ]);

        $create_invoice_item = InvoiceItemsModel::create([
            'invoice_id' => $create_invoice->id,
            'product_code' => $request->input('0.invoice_items.product_code'),
            'product_name' => $request->input('0.invoice_items.product_name'),
            'quantity' => $request->input('0.invoice_items.quantity'),
            'rate' => $request->input('0.invoice_items.rate'),
            'total' => $request->input('0.invoice_items.total'),
            'type' => $request->input('0.invoice_items.type'),
        ]);

        
        if (isset($create_invoice) && isset($create_invoice_item)) {


            $templateParams = [
                'name' => 'ace_new_invoice_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $create_invoice->user_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->invoice_number,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->date,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->amount,
                            ],
                        ],
                    ]
                ],
            ];
            
            $whatsAppUtility = new sendWhatsAppUtility();
            
            $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Invoice');

            return response()->json([
                'message' => 'Insert record successfully!',
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to insert!',
            ], 400);
        }  
    }
}