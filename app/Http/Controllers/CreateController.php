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
            // 'sku' => 'required|unique:t_products,sku',
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
                // 'sku' => $request->input('sku'),
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
                $basic_amount_total = 0;
                foreach ($get_basic_product as $basic_product) 
                {
                    $basic_amount_total += (($basic_product->rate) * ($basic_product->quantity));
                }
                
                $create_order_basic = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $basic_amount_total,
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
                        'total' => $basic_product->rate * $basic_product->quantity,
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
                $gst_amount_total = 0;
                foreach ($get_gst_product as $gst_product) {
                    $gst_amount_total += (($gst_product->rate) * ($gst_product->quantity));
                }

                $create_order_gst = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $gst_amount_total,
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
                        'total' => $gst_product->rate * $gst_product->quantity,
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
        if (!empty($create_order_basic)) {
            $data[] = $create_order_basic;
        }

        // Check if data_gst exists and is not null, then add it to the array
        if (!empty($create_order_gst)) {
            $data[] = $create_order_gst;
        }

        // Remove items from the cart for the user
        $get_remove_items = CartModel::where('user_id', $userId)->delete();

        if ($create_order_basic !== null || $create_order_gst !== null) {
            $generate_order_invoice = new InvoiceController();

            // Iterate through the $data array and add the corresponding invoices
            foreach ($data as &$order) {
                // Unset unwanted fields

                // Check if the current order is of type 'basic' and has an id
                if ($order->type === 'basic') {
                    // Generate invoice and append to the current order as 'pdf'
                    $order->pdf = $generate_order_invoice->generateorderInvoice($create_order_basic->id);
                }
                // Check if the current order is of type 'gst' and has an id
                elseif ($order->type === 'gst') {
                    // Generate invoice and append to the current order as 'pdf'
                    $order->pdf = $generate_order_invoice->generateorderInvoice($create_order_gst->id);
                }

                unset($order->updated_at, $order->created_at, $order->id);

            }

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
                'user_id' => 'required',
                'product_code' => 'required',
                'product_name' => 'required',
                'rate' => 'required',
                'quantity' => 'required',
                'type' => 'required',
            ]);
        }

        else
        {
            $request->merge(['user_id' => $get_user->id]);
        }
    
            $create_cart = CartModel::updateOrCreate(
				[
					'user_id' => $request->input('user_id'),
					'product_code' => $request->input('product_code'),
				], 
				[
					'product_name' => $request->input('product_name'),
					'rate' => $request->input('rate'),
					'quantity' => $request->input('quantity'),
					'amount' => ($request->input('rate')) * ($request->input('quantity')),
					'type' => $request->input('type'),
				]
			);

            unset($create_cart['id'], $create_cart['created_at'], $create_cart['updated_at']);


        return isset($create_cart) && $create_cart !== null
        ? response()->json(['Cart created successfully!', 'data' => $create_cart], 201)
        : response()->json(['Failed to create cart successfully!'], 400);

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
        // Decode the JSON string inside the 'data' key
        $json_data = json_decode($request->input('data'), true);

        if (is_array($json_data) && isset($json_data[0])) 
        {
            $data = $json_data[0]; // Access the first element of the decoded array

            // Create the invoice
            $create_invoice = InvoiceModel::create([
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'],
                'invoice_number' => $data['invoice_no'],
                'date' => $data['invoice_date'],
                'amount' => $data['amount'],
                'type' => $data['type'],
            ]);

            $invoice_items = $data['invoice_items']; // Get the invoice_items array

            // Initialize an array to store created invoice items
            $created_items = [];

            // Loop through the invoice items and insert each one
            foreach ($invoice_items as $item) {
                $created_item = InvoiceItemsModel::create([
                    'invoice_id' => $create_invoice->id,
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $item['total'],
                    'type' => $item['type'],
                ]);

            //     // Add each created item to the array
            //     $created_items[] = $created_item->toArray(); // Convert object to array for manipulation
            // }

            // // Remove 'updated_at', 'created_at', and 'id' from each created item
            // foreach ($created_items as &$item) {
            //     unset($item['updated_at'], $item['created_at'], $item['id']);

            //     // Add the modified item to the array
            //     $created_items[] = $created_item_array;
            // }

            // // Prepare the data array
            // $get_data = [];

            // // Check if create_invoice exists and is not null, then add it to the array
            // if(!empty($create_invoice))
            // {
            //     $get_data[] = $create_invoice;
            // }

            // // Check if created_items exists and is not null, then add it to the array
            // if(!empty($created_items))
            // {
            //     $get_data[] = $created_items;

            //     unset($created_items->updated_at, $created_items->created_at, $created_items->id);
            // }

            // if ($create_invoice !== null || $created_items !== null) 
            // {

            //     $generate_invoice = new InvoiceController();
    
            //      // This will store the invoices generated for display or further processing
            //     $invoices = [];
    
            //     $invoices = $generate_invoice->generateInvoice($create_invoice->id);
    
            //     // Add invoices to the $data array under a specific key
            //     $get_data['invoices'] = $invoices;

            //     unset($create_invoice->updated_at, $create_invoice->created_at, $create_invoice->id);
            // }

            // Add each created item to the array
            $created_items[] = $created_item->toArray(); // Convert object to array for manipulation
        }

        // Generate the invoice PDF using the invoice ID
        $generate_invoice = new InvoiceController();
        $invoice_pdf = $generate_invoice->generateInvoice($create_invoice->id); // You need the invoice ID here

        // Prepare the data array
        $get_data = [];

        // Add the created invoice to $get_data, but keep the 'id' for generating the invoice PDF first
        if (!empty($create_invoice)) {
            $invoice_array = $create_invoice->toArray();
            unset($invoice_array['updated_at'], $invoice_array['created_at']); // Remove created_at and updated_at
            // Do not unset 'id' here yet because we need it for the invoice generation
            $get_data[] = $invoice_array;
        }

        // Add the created items to $get_data after unsetting unnecessary fields
        foreach ($created_items as &$item) {
            unset($item['updated_at'], $item['created_at'], $item['id']); // Remove fields from each item
        }

        if (!empty($created_items)) {
            $get_data[] = $created_items;
        }

            // Now, after the invoice PDF is generated, you can safely remove the invoice 'id'
            unset($get_data[0]['id']); // Remove 'id' from the invoice data in the final response

            // Add the invoice PDF link to the data
            $get_data['invoices'] = $invoice_pdf;


            // Return a detailed response with the created invoice and invoice items
            return response()->json([
                'message' => 'Invoice and items created successfully!',
                // 'invoice' => $create_invoice, // Return the created invoice
                // 'invoice_items' => $created_items // Return the created invoice items
                'data' => $get_data
            ], 200);
        } 
        else 
        {
            return response()->json(['message' => 'Invalid data format'], 400);
        }
    }
        
}