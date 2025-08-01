<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\CartModel;

use App\Models\OrderModel;

use App\Models\ProductModel;

use App\Models\OrderItemsModel;

use App\Models\StockCartModel;

use App\Models\StockOrdersModel;

use App\Models\StockOrderItemsModel;

use App\Http\Controllers\InvoiceController;

use App\Http\Controllers\WishlistController;

use App\Utils\sendWhatsAppUtility;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

use Laravel\Sanctum\PersonalAccessToken;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;


class UpdateController extends Controller
{
    //
    public function user(Request $request)
    {
        $get_user = Auth::id();

        $request->validate([
            // 'mobile' => ['required', 'string'],
            'password' => 'required',
            // 'name' => ['required', 'string'],
            
        ]);

        $update_user_record = User::where('id', $get_user)
        ->update([
            'password' => bcrypt($request->input('password')),
            // 'email' => strtolower($request->input('email')),
            // 'mobile' => $request->input('mobile'),
            // 'role' => $request->input('role'),
            // 'address_line_1' => $request->input('address_line_1'),
            // 'address_line_2' => $request->input('address_line_2'),
            // 'city' => $request->input('city'),
            // 'pincode' => $request->input('pincode'),
            // 'gstin' => $request->input('gstin'),
            // 'state' => $request->input('state'),
            // 'country' => $request->input('country'),
        ]);

        if ($update_user_record == 1) {
            return response()->json([
                'message' => 'User record updated successfully!',
                'data' => $update_user_record
            ], 200);
        }
        
        else {
            return response()->json([
                'message' => 'Failed to user record successfully'
            ], 400);
        }
    }

    public function updateUserType(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'user_id' => 'required|exists:users,id', // Ensure that the user_id exists in the users table
            'type' => 'required|string', // Add validation for type (e.g., role, address, etc.)
        ]);

        // Get the user_id and type from the request
        $user_id = $request->input('user_id');
        $type = $request->input('type');

        // Based on the type, you can update specific fields of the user
        $updateData = [];
        $updateData['type'] = $request->input('type');
        
        // Update the user record with the given data
        $update_user_record = User::where('id', $user_id)->update($updateData);

        // Check if the update was successful
        if ($update_user_record) {
            return response()->json([
                'message' => 'User record updated successfully!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to update user record.',
            ], 400);
        }
    }


    public function inactivate_user(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'user_id' => 'required|exists:users,id', // Ensure that the user_id exists in the users table
        ]);

        // Get the user_id from the request
        $user_id = $request->input('user_id');

        // Start a database transaction to ensure atomic updates
        DB::beginTransaction();

        try {
            // Inactivate the user (set is_verified to 0)
            $updateData = ['is_verified' => '0'];
            $update_user_record = User::where('id', $user_id)->update($updateData);

            // Remove all personal access tokens associated with this user
            PersonalAccessToken::where('tokenable_id', $user_id)->delete();

            // Commit the transaction if all operations are successful
            DB::commit();

            return response()->json([
                'message' => 'User inactivated and tokens removed successfully!',
            ], 200);

        } catch (\Exception $e) {
            // Rollback in case of any error
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to inactivate user and remove tokens.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    // public function __construct(sendWhatsAppUtility $whatsapputility)
    // {
    //     $this->whatsapputility = $whatsapputility;
    // }

    public function generate_otp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'size:13'],
        ]);

        $mobile = $request->input('mobile');

        $get_user = User::select('id')
            ->where('mobile', $mobile)
            ->first();
            
        if (!$get_user == null) {

            $six_digit_otp_number = random_int(100000, 999999);

            $expiresAt = now()->addMinutes(10);

            $store_otp = User::where('mobile', $mobile)
                ->update([
                    'otp' => $six_digit_otp_number,
                    'expires_at' => $expiresAt,
                ]);
            
            if ($store_otp) {

                // $templateParams = [
                //     'name' => 'ace_otp', // Replace with your WhatsApp template name
                //     'language' => ['code' => 'en'],
                //     'components' => [
                //         [
                //             'type' => 'body',
                //             'parameters' => [
                //                 [
                //                     'type' => 'text',
                //                     'text' => $six_digit_otp_number,
                //                 ],
                //             ],
                //         ],
                //     ],
                // ];

                // // Directly create an instance of SendWhatsAppUtility
                // $whatsAppUtility = new sendWhatsAppUtility();

                // // Send OTP via WhatsApp
                // // $whatsAppUtility->sendOtp("+918961043773", $templateParams);
                // $response = $whatsAppUtility->sendWhatsApp("+918961043773", $templateParams, "+918961043773", 'OTP Campaign');
                $templateParams = [
                    'name' => 'ace_otp', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp_number,
                                ],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            "index" => "0",
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp_number,
                                ],
                            ],
                        ]
                    ],
                ];
                
                // Directly create an instance of SendWhatsAppUtility
                $whatsAppUtility = new sendWhatsAppUtility();
                
                // Send OTP via WhatsApp
                // $response = $whatsAppUtility->sendWhatsApp("+918961043773", $templateParams, "+918961043773", 'OTP Campaign');
                $response = $whatsAppUtility->sendWhatsApp($mobile, $templateParams, $mobile, 'OTP Campaign');
                
                // Send OTP via WhatsApp
                // $response = $this->whatsAppService->sendOtp("+918961043773", $templateParams);

                // dd($response);

                return response()->json([
                    'message' => 'Otp store successfully!',
                    'data' => $store_otp
                ], 200);
            }

        else {
                return response()->json([
                'message' => 'Fail to store otp successfully!',
                'data' => $store_otp
                ], 501);
            }
        }

        else {
            return response()->json([
                'message' => 'User has not registered!',
            ], 200);
            // no-register user will be registered as a guest user and otp will be send
            // $create_guest_user = User::create([
            //     'name' => "guest",
            //     'password' => bcrypt($request->input('mobile')),
            //     'mobile' => $request->input('mobile'),
            //     'type' => 'guest',
            //     'is_verified' => '0',
            // ]);

            // if (isset($create_guest_user)) {

            //     $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();

            //     $templateParams = [
            //         'name' => 'ace_new_user_registered', // Replace with your WhatsApp template name
            //         'language' => ['code' => 'en'],
            //         'components' => [
            //             [
            //                 'type' => 'body',
            //                 'parameters' => [
            //                     [
            //                         'type' => 'text',
            //                         'text' => $create_guest_user->name,
            //                     ],
            //                     [
            //                         'type' => 'text',
            //                         'text' => $create_guest_user->mobile,
            //                     ],
            //                     [
            //                         'type' => 'text',
            //                         'text' => '-',
            //                     ],
            //                 ],
            //             ]
            //         ],
            //     ];
                
            //     $whatsAppUtility = new sendWhatsAppUtility();
                
            //     foreach ($mobileNumbers as $mobileNumber)
            //     {
            //         // Send message for each number
    
            //         $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'User Register');
    
            //         // Decode the response into an array
            //         $responseArray = json_decode($response, true);
    
            //         // Check if the response has an error or was successful
            //         if (isset($responseArray['error'])) 
            //         {
            //             echo "Failed to send message to Whatsapp!";
            //         } 
            //     }    

            //     $six_digit_otp_number = random_int(100000, 999999);

            //     $expiresAt = now()->addMinutes(10);

            //     $store_otp = User::where('mobile', $mobile)
            //                         ->update([
            //                             'otp' => $six_digit_otp_number,
            //                             'expires_at' => $expiresAt,
            //                         ]);
            //     if ($store_otp)     
            //     {

            //         $templateParams = [
            //                         'name' => 'ace_otp', // Replace with your WhatsApp template name
            //                         'language' => ['code' => 'en'],
            //                         'components' => [
            //                             [
            //                                 'type' => 'body',
            //                                 'parameters' => [
            //                                     [
            //                                         'type' => 'text',
            //                                         'text' => $six_digit_otp_number,
            //                                     ],
            //                                 ],
            //                             ],
            //                             [
            //                                 'type' => 'button',
            //                                 'sub_type' => 'url',
            //                                 "index" => "0",
            //                                 'parameters' => [
            //                                     [
            //                                         'type' => 'text',
            //                                         'text' => $six_digit_otp_number,
            //                                     ],
            //                                 ],
            //                             ]
            //                         ],
            //                     ];
                                
            //                     // Directly create an instance of SendWhatsAppUtility
            //                     $whatsAppUtility = new sendWhatsAppUtility();
                                
            //                     // Send OTP via WhatsApp
            //                     $response = $whatsAppUtility->sendWhatsApp($mobile, $templateParams, $mobile, 'OTP Campaign');
                
            //                     return response()->json([
            //                         'message' => 'Otp store successfully!',
            //                         'data' => $store_otp
            //                     ], 200);
            //     }

            //     else {
            //         return response()->json([
            //         'message' => 'Fail to store otp successfully!',
            //         'data' => $store_otp
            //         ], 501);
            //     }
            // }

            // else {
            //     return response()->json([
            //         'message' => 'Sorry, please Try Again!',
            //     ], 500);
            // }  
        }
    }

    public function cart(Request $request, $id)
    {
        $get_user = Auth::User();
        
        if($get_user->role == 'admin')
        {
            $request->validate([
                'user_id' => 'required',
                // 'products_id' => 'required',
                'product_code' => 'required',
                'rate' => 'required',
                'quantity' => 'required',
                // 'amount' => 'required',
                'type' => 'required',
            ]);
    
            $update_cart = CartModel::where('id', $id)
            ->update([
                // 'products_id' => $request->input('products_id'),
                'product_code' => $request->input('product_code'),
                'quantity' => $request->input('quantity'),
                'rate' => $request->input('rate'),
                'amount' => ($request->input('rate')) * ($request->input('quantity')),
                'type' => $request->input('type'),
                'remarks' => $request->input('remarks'),
                'size' => $request->input('size'),
            ]);
        }
        else {
            $request->validate([
                // 'products_id' => 'required',
                'product_code' => 'required',
                // 'rate' => 'required',
                'quantity' => 'required',
                // 'amount' => 'required',
                'type' => 'required',
            ]);
    
                $update_cart = CartModel::where('id', $id)
                ->update([
                    // 'products_id' => $request->input('products_id'),
                    'product_code' => $request->input('product_code'),
                    'quantity' => $request->input('quantity'),
                    'type' => $request->input('type'),
                    'remarks' => $request->input('remarks'),
                    'size' => $request->input('size'),
                ]);
        }

        if ($update_cart == 1) {
            return response()->json([
                'message' => 'Cart updated successfully!',
                'data' => $update_cart
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed to update cart successfully!'
            ], 400);
        }    
    }

    public function verify_user(Request $request, $get_id)
    {
        $request->validate([
            'type' => 'nullable|string|regex:/^[a-zA-Z\s]*$/',
        ]);

        $update_verify = User::where('id', $get_id)
             ->update([
                 'is_verified' => '1',
                 'type' => $request->input('type'),
             ]);

             $user = User::select('name', 'mobile')
                          ->where('id', $get_id)
                          ->first();
            
            // $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();

            // Find the user by ID and toggle is_verified
            //$user = User::findOrFail($get_id);
            //$user->is_verified = '1';
            //$user->type = $request->input('type');
            //$update_verify = $user->save();

            // Retrieve the name and mobile of the user
            //$userData = $user->only(['name', 'mobile']);


            if ($update_verify == 1) {

                $templateParams = [
                    'name' => 'ace_user_approved', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $user->name,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => substr($user->mobile, -10),
                                ],
                            ],
                        ]
                    ],
                ];
                
                // Directly create an instance of SendWhatsAppUtility
                $whatsAppUtility = new sendWhatsAppUtility();
                
                $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'Approve Client');
                $response = $whatsAppUtility->sendWhatsApp('918961043773', $templateParams, '', 'Approve Client');
                $response = $whatsAppUtility->sendWhatsApp('919966633307', $templateParams, '', 'Approve Client');

                // Decode the response into an array
                $responseArray = json_decode($response, true);

                // Check if the response has an error or was successful
                if (isset($responseArray['error'])) {
                    return response()->json([
                        'message' => 'Error!',
                    ], 503);
                } else 
                {
                    return response()->json([
                         'message' => 'User verified successfully!',
                         'data' => $update_verify
                     ], 200);
                  // Check if the user is now verified or unverified
                    //$statusMessage = $user->is_verified == 1 ? 'User verified successfully!' : 'User unverified successfully!';

                    //return response()->json([
                        //'message' => $statusMessage,
                        //'data' => $user->is_verified, // Returns the is_verified status (1 or 0)
                    //], 200);
                }
            }
    
            else {
                return response()->json([
                    'message' => 'Sorry, failed to update'
                ], 400);
            }    
    }

    public function unverify_user(Request $request, $get_id)
    {

        $update_verify = User::where('id', $get_id)
             ->update([
                 'is_verified' => '0',
             ]);

             $user = User::select('name', 'mobile')
                          ->where('id', $get_id)
                          ->first();

            if ($update_verify == 1) {

                return response()->json([
                        'message' => 'User verified successfully!',
                        'data' => $update_verify
                    ], 200);
            }
    
            else {
                return response()->json([
                    'message' => 'Sorry, failed to update'
                ], 400);
            }    
    }

    public function updatePurchaseLock(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'purchase_lock' => 'required|in:0,1',  // Ensure purchase_lock is either 0 or 1
            'user_id' => 'required|exists:users,id', // Ensure user_id exists in the users table
        ]);

        try {
            // Find the user by ID
            $user = User::findOrFail($request->input('user_id'));

            // Update the purchase_lock value
            $user->purchase_lock = $validated['purchase_lock'];

            // Save the changes
            $user->save();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Purchase lock status updated successfully.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            // Return an error response if anything goes wrong
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the purchase lock status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function edit_order(Request $request, $id)
    // {
    //     $get_user = Auth::User();

    //     // Validate incoming request data
    //     $request->validate([
    //         'order_id' => 'required|string',
    //         'order_type' => 'required|string',
    //         'user_id' => 'required|integer',
    //         'amount' => 'required|numeric',
    //         'items' => 'required|array',
    //         'items.*.product_code' => 'required|string',
    //         'items.*.product_name' => 'required|string',
    //         'items.*.quantity' => 'required|integer',
    //         'items.*.rate' => 'required|numeric',
    //         'items.*.total' => 'required|numeric',
    //         'items.*.remarks' => 'nullable|string',
    //         'items.*.markedForDeletion' => 'nullable|boolean',
    //         'items.*.removalReason' => 'nullable|string',
    //     ]);

    //     // Find the order by its ID
    //     $order = OrderModel::find($id);

    //     if (!$order) {
    //         return response()->json([
    //             'message' => 'Order not found!'
    //         ], 404);
    //     }

    //     // Check if the order belongs to the provided user_id
    //     if ($order->user_id !== $request->input('user_id')) {
    //         return response()->json([
    //             'message' => 'Unauthorized action. This order does not belong to the specified user.'
    //         ], 403);
    //     }

    //     // Update the order details
    //     $order->amount = $request->input('amount');
    //     $order->save();

    //     // 🟡 Save current order items in associative array by product_code
    //     $existingItems = OrderItemsModel::where('order_id', $id)
    //     ->get()
    //     ->keyBy('product_code');

    //     // 🔴 Delete all old items
    //     OrderItemsModel::where('order_id', $id)->delete();

    //     $user_id = $order->user_id;

    //     // Fetch user type
    //     $user_type = User::select('type')->where('id', $user_id)->first();

    //     // Add the updated items to the order
    //     $items = $request->input('items');
    //     foreach ($items as $item) {
    //         // Skip the items marked for deletion
    //         if ($item['markedForDeletion']) {
    //             if ($item['removalReason'] === 'Not in stock') {
    //                 // Save to wishlist table if removalReason is "Not in Stock"
    //                 $wishlistController = new WishlistController();
    //                 $wishlistController->saveToWishlist($user_id, $item);  // Instance call
    //             }
    //             continue; // Skip further processing for this item
    //         }

    //         $product = ProductModel::where('product_code', $item['product_code'])->first();
    //         if (!$product) {
    //             return response()->json(['message' => "Product {$item['product_code']} not found."], 404);
    //         }

    //         // Default values
    //         $rate = $item['rate'];
    //         $total = $item['total'];

    //         // if ($get_user->mobile === "+918961043773") {
    //         //     // 🔵 Check if this product already existed in the order
    //         //     if ($existingItems->has($item['product_code'])) {
    //         //         // Use previously stored rate
    //         //         $rate = $existingItems[$item['product_code']]->rate;
    //         //     } else {
    //         //         // Get rate based on type and order pricing type (basic/gst)
    //         //         if ($order->type == 'basic') {
    //         //             $rate = match ($user_type->type ?? null) {
    //         //                 'special' => $product->special_basic ?? 0,
    //         //                 'outstation' => $product->outstation_basic ?? 0,
    //         //                 'zeroprice' => 0,
    //         //                 'guest' => $product->guest_price ?? 0,
    //         //                 default => $product->basic ?? 0,
    //         //             };
    //         //         } else {
    //         //             $rate = match ($user_type->type ?? null) {
    //         //                 'special' => $product->special_gst ?? 0,
    //         //                 'outstation' => $product->outstation_gst ?? 0,
    //         //                 'zeroprice', 'guest' => 0,
    //         //                 default => $product->gst ?? 0,
    //         //             };
    //         //         }
    //         //     }

    //         //     // Update total based on resolved rate
    //         //     $total = $rate * $item['quantity'];
    //         // }
            
    //         OrderItemsModel::create([
    //             'order_id' => $id,
    //             'product_code' => $item['product_code'],
    //             'product_name' => $item['product_name'],
    //             'quantity' => $item['quantity'],
    //             'rate' => $item['rate'],
    //             'total' => $item['total'],
    //             'type' => strtolower($request->input('order_type')),
    //             'remarks' => $item['remarks'] ?? '',
    //         ]);
    //     }

    //     if ($get_user->mobile != "+918961043773") {
    //         $generate_order_invoice = new InvoiceController();
    //         $generate_order_invoice->generateorderInvoice($id, true);
    //         $generate_order_invoice->generatePackingSlip($id, true);
    //     }

    //     return response()->json([
    //         'message' => 'Order updated successfully!',
    //         'order' => $order,
    //         'items' => $items
    //     ], 200);
    // }

    public function edit_order(Request $request, $id)
    {
        $get_user = Auth::User();

        $request->validate([
            'order_id' => 'required|string',
            'order_type' => 'required|string',
            'user_id' => 'required|integer',
            'amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_code' => 'required|string',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.rate' => 'required|numeric',
            'items.*.total' => 'required|numeric',
            'items.*.remarks' => 'nullable|string',
            'items.*.markedForDeletion' => 'nullable|boolean',
            'items.*.removalReason' => 'nullable|string',
            'cancel_order_id' => 'nullable|string',
        ]);

        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        if ($order->user_id !== $request->input('user_id')) {
            return response()->json([
                'message' => 'Unauthorized action. This order does not belong to the specified user.'
            ], 403);
        }

        

        // $cancelOrderIds = array_filter(explode(',', $request->input('cancel_order_id')));
        $cancelOrderIds = array_filter(array_map('trim', explode(',', $request->input('cancel_order_id'))));

        // if (!empty($cancelOrderIds)) {
        //     // Cancel all mentioned order IDs
        //     foreach ($cancelOrderIds as $cancelId) {
        //         $cancelId = trim($cancelId);
        //         if (!empty($cancelId)) {
        //             $cancelOrder = OrderModel::find($cancelId);
        //             if ($cancelOrder) {
        //                 $cancelOrder->status = 'cancelled'; // use actual column name if different
        //                 $cancelOrder->save();
        //             } else {
        //                 Log::warning("Cancel Order ID {$cancelId} not found");
        //             }
        //         }
        //     }
        // }

        $is_merged = false;
        $merged_orders = '';
        DB::beginTransaction();

        try {
            // $order->amount = $request->input('amount');
            // $order->save();
            if (!empty($cancelOrderIds)) {
                foreach ($cancelOrderIds as $cancelId) {
                    
                    $cancelOrder = OrderModel::find($cancelId);
                    if ($cancelOrder) {
                        $cancelOrder->status = "cancelled";
                        $cancelOrder->save();

                        // Append the cancelled order_id to the merged_orders string
                        if ($merged_orders) {
                            $merged_orders .= ', '; // Add a comma separator between IDs
                        }
                        $merged_orders .= $cancelOrder->order_id; // Add the current order_id
                    } else {
                        Log::warning("Cancel Order ID {$cancelId} not found");
                    }
                    $is_merged = true;
                }
            }

            $existingItems = OrderItemsModel::where('order_id', $id)->get()->keyBy('product_code');
            OrderItemsModel::where('order_id', $id)->delete();

            $user_id = $order->user_id;
            $user_type = User::select('type')->where('id', $user_id)->first();
            $items = $request->input('items');
            $calculatedAmount = 0;

            Log::error('Edit Order Data', [
                'order_id' => $id,
                'user_id' => $get_user->id ?? null,
                'items' => $items
            ]);

            foreach ($items as $item) {
                if ($item['markedForDeletion'] ?? false) {
                    if (($item['removalReason'] ?? '') === 'Not in stock') {
                        $wishlistController = new WishlistController();
                        $wishlistController->saveToWishlist($user_id, $item);
                    }
                    continue;
                }

                $product = ProductModel::where('product_code', $item['product_code'])->first();
                if (!$product) {
                    throw new \Exception("Product {$item['product_code']} not found.");
                }

                OrderItemsModel::create([
                    'order_id' => $id,
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $item['total'],
                    'type' => strtolower($request->input('order_type')),
                    'remarks' => $item['remarks'] ?? '',
                ]);

                $calculatedAmount += $item['total'];
            }

            // Set order amount based on sum of item totals, not input
            $order->amount = $calculatedAmount;
            $order->save();

            // if ($get_user->mobile != "+918961043773") {
                $generate_order_invoice = new InvoiceController();
                $generate_order_invoice->generateorderInvoice($id, true, $is_merged, $merged_orders);
                $generate_order_invoice->generatePackingSlip($id, true);
            // }

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully!',
                'order' => $order,
                'items' => $items
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to edit order', [
                'order_id' => $id,
                'user_id' => $get_user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function complete_order(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Check if the order belongs to the provided user_id
        if ($order->user_id !== $request->input('user_id')) {
            return response()->json([
                'message' => 'Unauthorized action. This order does not belong to the specified user.'
            ], 403);
        }

        // Update the status of the order to 'completed'
        $order->status = 'completed';
        $order->save();

        return response()->json([
            'message' => 'Order status updated to completed successfully!',
            'order' => $order
        ], 200);
    }

    public function cancel_order(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Check if the order belongs to the provided user_id
        if ($order->user_id !== $request->input('user_id')) {
            return response()->json([
                'message' => 'Unauthorized action. This order does not belong to the specified user.'
            ], 403);
        }

        // Update the status of the order to 'completed'
        $order->status = 'cancelled';
        $order->save();

        $user = User::find($order->user_id);
        $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();

        $whatsAppUtility = new sendWhatsAppUtility();

        $templateParams = [
            'name' => 'ace_order_cancelled', // Replace with your WhatsApp template name
            'language' => ['code' => 'en'],
            'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $user->name,
                        ],
                        [
                            'type' => 'text',
                            'text' => $order->order_id,
                        ],
                        [
                            'type' => 'text',
                            'text' => Carbon::parse($order->order_date)->format('d-m-Y'),
                        ],
                        [
                            'type' => 'text',
                            'text' => $order->amount,
                        ],
                    ],
                ]
            ],
        ];

        foreach ($mobileNumbers as $mobileNumber) 
        {
            if($mobileNumber == '+918961043773' || true)
            {
                // Send message for each number
                $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Order Cancel Notification');
            }
        }

        $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'Order Cancel Notification');

        return response()->json([
            'message' => 'Order has been cancelled successfully!',
            'order' => $order
        ], 200);
    }

    public function stock_cart_update(Request $request, $id)
    {
        // Fetch the stock cart item for the authenticated user
        $stockCartItem = StockCartModel::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        // Return response using ternary operator if the item is not found
        return !$stockCartItem
            ? response()->json(['message' => 'Stock cart item not found.', 'count' => 0], 404)
            : (function () use ($request, $stockCartItem) {
                // Validate the request
                $validated = $request->validate([
                    'product_code' => 'required|string|exists:t_products,product_code',
                    'product_name' => 'required|string|exists:t_products,product_name',
                    'quantity' => 'required|integer|min:1',
                    'godown_id' => 'required|integer|exists:t_godown,id',
                    'type' => 'required|in:IN,OUT',
                ]);

                // Update the stock cart item
                $stockCartItem->update([
                    'product_code' => $validated['product_code'],
                    'product_name' => $validated['product_name'],
                    'quantity' => $validated['quantity'],
                    'godown_id' => $validated['godown_id'],
                    'type' => $validated['type'],
                ]);

                // Return the success response
                return response()->json([
                    'message' => 'Stock cart item updated successfully.',
                    'data' => $stockCartItem->makeHidden(['id', 'updated_at', 'created_at']),
                ], 200);
            })();
    }


    // public function updateStockOrder(Request $request, $orderId)
    // {
    //     try {
    //         // Validate the request
    //         $validated = $request->validate([
    //             'remarks' => 'nullable|string|max:255',
    //         ]);

    //         // Fetch the stock order by order_id
    //         $stockOrder = StockOrderModel::where('order_id', $orderId)->first();

    //         if (!$stockOrder) {
    //             return response()->json([
    //                 'message' => 'Stock order not found.',
    //                 'status' => 'false',
    //             ], 404);
    //         }

    //         // Fetch the current user's ID
    //         $userId = Auth::id();

    //         if ($stockOrder->user_id !== $userId) {
    //             return response()->json([
    //                 'message' => 'Unauthorized to update this stock order.',
    //                 'status' => 'false',
    //             ], 403);
    //         }

    //         // Fetch cart items for the user
    //         $cartItems = StockCartModel::where('user_id', $userId)->get();

    //         if ($cartItems->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'No items found in the stock cart for the user.',
    //                 'status' => 'false',
    //             ], 404);
    //         }

    //         // Determine the order type from the first cart item
    //         $orderType = $cartItems->first()->type;

    //         // Update the stock order
    //         $stockOrder->update([
    //             'type' => $orderType,
    //             'remarks' => $validated['remarks'] ?? $stockOrder->remarks,
    //         ]);

    //         // Remove existing stock order items
    //         StockOrderItemModel::where('stock_order_id', $stockOrder->id)->delete();

    //         // Create new stock order items from the cart
    //         foreach ($cartItems as $item) {
    //             StockOrderItemModel::create([
    //                 'stock_order_id' => $stockOrder->id,
    //                 'product_code' => $item->product_code,
    //                 'product_name' => $item->product_name,
    //                 'quantity' => $item->quantity,
    //                 'type' => $item->type,
    //             ]);
    //         }

    //         // Clear the stock cart after updating the order
    //         StockCartModel::where('user_id', $userId)->delete();

    //         return response()->json([
    //             'message' => 'Stock order updated successfully.',
    //             'data' => [
    //                 'order_id' => $stockOrder->order_id,
    //                 'order_date' => $stockOrder->order_date,
    //                 'type' => $stockOrder->type,
    //                 'remarks' => $stockOrder->remarks,
    //                 'items' => $cartItems->map(function ($item) {
    //                     return $item->only(['product_code', 'product_name', 'quantity', 'type']);
    //                 }),
    //             ],
    //             'status' => 'true',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while updating the stock order.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function updateStockOrder(Request $request, $orderId)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'remarks' => 'nullable|string|max:255',
                'items' => 'required|array',
                'items.*.product_code' => 'required|string|exists:t_products,product_code',
                'items.*.product_name' => 'required|string|exists:t_products,product_name',
                'items.*.godown_id' => 'required|integer|exists:t_godown,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.type' => 'required|in:IN,OUT',
            ]);

            // Fetch the stock order by order_id
            $stockOrder = StockOrdersModel::where('id', $orderId)->first();

            if (!$stockOrder) {
                return response()->json([
                    'message' => 'Stock order not found.',
                    'status' => 'false',
                ], 404);
            }

            // Fetch the current user's ID
            $userId = Auth::id();

            // Ensure the stock order belongs to the authenticated user
            if ($stockOrder->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized to update this stock order.',
                    'status' => 'false',
                ], 403);
            }

            // Update the stock order
            $stockOrder->update([
                'remarks' => $validated['remarks'] ?? $stockOrder->remarks,
            ]);

            // Remove existing stock order items
            StockOrderItemsModel::where('stock_order_id', $stockOrder->id)->delete();

            // Add new items to the stock order
            $items = $validated['items'];
            foreach ($items as $item) {
                StockOrderItemsModel::create([
                    'stock_order_id' => $stockOrder->id,
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name'],
                    'godown_id' => $item['godown_id'],
                    'quantity' => $item['quantity'],
                    'type' => $item['type'],
                ]);
            }

            $generate_stock_order_invoice = new InvoiceControllerZP();
            $stockOrder->pdf = $generate_stock_order_invoice->generatestockorderInvoice($stockOrder->id, true);

            return response()->json([
                'message' => 'Stock order updated successfully.',
                'data' => [
                    'order_id' => $stockOrder->order_id,
                    'order_date' => $stockOrder->order_date,
                    'type' => $stockOrder->type,
                    'godown' => $stockOrder->godown_id,
                    'remarks' => $stockOrder->remarks,
                    'items' => $items,
                ],
                'status' => 'true',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the stock order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // split order
    // public function splitOrder(Request $request, $id)
    // {
    //     $data = $request->validate([
    //         'items'                    => 'required|array',
    //         'items.*.product_code'     => 'required|string|exists:t_order_items,product_code',
    //         'items.*.size'             => 'nullable|string',
    //         'items.*.quantity'         => 'required|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // 1) Load original order + items
    //         /** @var OrderModel $order */
    //         $order = OrderModel::with('order_items')
    //                     ->findOrFail($id);

    //         // 2) Build a map of product_code → quantity
    //         // $keepMap = collect($data['items'])
    //         //             ->keyBy('product_code')
    //         //             ->map(fn($i) => $i['quantity']);

    //         $keepMap = collect($data['items'])
    //             ->keyBy(function($item) {
    //                 return $item['product_code'] . '_' . $item['size'];
    //             })
    //             ->map(fn($i) => $i['quantity']);

    //         // 3) Calculate totals BEFORE punching any order
    //         $originalTotal = 0;
    //         $movedTotal    = 0;

    //         foreach ($order->order_items as $item) {
    //             $origQty = $item->quantity;
    //             $key = $item->product_code . '_' . $item->size; // Create key based on product_code + size
    //             // $keepQty = $keepMap->get($item->product_code, 0);
    //             $keepQty = $keepMap->get($key, 0);

    //             if ($keepQty > $origQty) {
    //                 throw new \Exception("Cannot keep {$keepQty} > existing {$origQty} for product {$item->product_code}");
    //             }

    //             $moveQty = $origQty - $keepQty;

    //             // sum kept vs. moved
    //             $originalTotal += ($keepQty * $item->rate);
    //             $movedTotal    += ($moveQty * $item->rate);
    //         }

    //         // 4) Build new order_id (append “A” to segment #2)
    //         $parts      = explode('/', $order->order_id);
    //         $parts[1]  .= 'A';
    //         $newOrderCode = implode('/', $parts);

    //         // 5) Create the new order with the correct movedTotal
    //         $newOrder = OrderModel::create([
    //             'user_id'    => $order->user_id,
    //             'order_id'   => $newOrderCode,
    //             'order_date' => Carbon::now(),
    //             'amount'     => $movedTotal,
    //             'type'       => $order->type,
    //         ]);

    //         // 6) Update the original order’s amount
    //         $order->update(['amount' => $originalTotal]);

    //         // 7) Now split or move each item‑row
    //         foreach ($order->order_items as $item) {
    //             $origQty = $item->quantity;
    //             $key = $item->product_code . '_' . $item->size; // Create key based on product_code + size
    //             // $keepQty = $keepMap->get($item->product_code, 0);
    //             $keepQty = $keepMap->get($key, 0);
    //             $moveQty = $origQty - $keepQty;

    //             // If quantity is zero, delete the item
    //             if ($moveQty == 0) {
    //                 $item->delete();
    //                 continue;
    //             }

    //             if ($keepQty === $origQty) {
    //                 continue;
    //             }

    //             if ($keepQty > 0) {
    //                 // Shrink the original row
    //                 $item->update([
    //                     'quantity' => $keepQty,
    //                     'total'    => $keepQty * $item->rate,
    //                 ]);

    //                 // Create the remainder for the new order
    //                 OrderItemsModel::create([
    //                     'order_id'     => $newOrder->id,
    //                     'product_code' => $item->product_code,
    //                     'product_name' => $item->product_name,
    //                     'rate'         => $item->rate,
    //                     'quantity'     => $moveQty,
    //                     'total'        => $moveQty * $item->rate,
    //                     'type'         => $item->type,
    //                     'remarks'      => $item->remarks,
    //                     'size'         => $item->size,
    //                 ]);
    //             } else {
    //                 // Move the entire row
    //                 $item->update(['order_id' => $newOrder->id]);
    //             }
    //         }

    //         DB::commit();

    //         // Generate invoices for both orders
    //         $generate_order_invoice = new InvoiceController();
    //         $generate_order_invoice->generateorderInvoice($order->id, true);
    //         $generate_order_invoice->generatePackingSlip($order->id, true);

    //         $generate_order_invoice->generateorderInvoice($newOrder->id, true);
    //         $generate_order_invoice->generatePackingSlip($newOrder->id, true);


    //         return response()->json([
    //             'message'        => 'Order split successfully.',
    //             'original_order' => $order->fresh('order_items'),
    //             'new_order'      => $newOrder->load('order_items'),
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error("Order split error: {$e->getMessage()}");

    //         return response()->json([
    //             'message' => 'Failed to split order.',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function splitOrder(Request $request, $id)
    // {
    //     $data = $request->validate([
    //         'items'                    => 'required|array',
    //         'items.*.product_code'     => 'required|string|exists:t_order_items,product_code',
    //         'items.*.size'             => 'nullable|string',
    //         'items.*.quantity'         => 'required|integer|min:0',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // 1) Load original order + items
    //         /** @var OrderModel $order */
    //         $order = OrderModel::with('order_items')
    //                     ->findOrFail($id);

    //         // 2) Build a map of product_code + size → quantity (using product_code + size for uniqueness)
    //         $keepMap = collect($data['items'])
    //             ->keyBy(function($item) {
    //                 return $item['product_code'] . '_' . $item['size'];
    //             })
    //             ->map(fn($i) => $i['quantity']);

    //         // 3) Calculate totals BEFORE punching any order
    //         $originalTotal = 0;
    //         $movedTotal    = 0;

    //         foreach ($order->order_items as $item) {
    //             $origQty = $item->quantity;
    //             $key = $item->product_code . '_' . $item->size; // Create key based on product_code + size
    //             $keepQty = $keepMap->get($key, 0);

    //             // sum kept vs. moved
    //             $originalTotal += ($keepQty * $item->rate);

    //             // If quantity is 0, the entire original quantity is moved to the new order (child)
    //             if ($keepQty == 0) {
    //                 $movedTotal    += ($origQty * $item->rate); // Moving all quantity to the new order
    //             } else {
    //                 $movedTotal    += ($keepQty * $item->rate); // Only keeping the quantity in the original order
    //             }
    //         }

    //         // 4) Build new order_id (append “A” to segment #2)
    //         $parts      = explode('/', $order->order_id);
    //         $parts[2]  .= 'SPL';
    //         $newOrderCode = implode('/', $parts);

    //         // 5) Create the new order with the correct movedTotal
    //         $newOrder = OrderModel::create([
    //             'user_id'    => $order->user_id,
    //             'order_id'   => $newOrderCode,
    //             'order_date' => Carbon::now(),
    //             'amount'     => $movedTotal,
    //             'type'       => $order->type,
    //         ]);

    //         // 6) Update the original order’s amount
    //         $order->update(['amount' => $originalTotal]);

    //         // 7) Now split or move each item‑row
    //         foreach ($order->order_items as $item) {
    //             $origQty = $item->quantity;
    //             $key = $item->product_code . '_' . $item->size; // Create key based on product_code + size
    //             $keepQty = $keepMap->get($key, 0);
    //             $moveQty = $origQty - $keepQty;

    //             // If quantity is 0 for the main table, move the entire quantity to the new order (child)
    //             if ($moveQty == 0 && $keepQty == 0) {
    //                 // Move the full quantity to the child order (new order)
    //                 $item->update(['order_id' => $newOrder->id, 'quantity' => $origQty]);
    //                 continue;
    //             }

    //             if ($keepQty === $origQty) {
    //                 continue; // Nothing to move, skip
    //             }

    //             if ($keepQty > 0) {
    //                 // Shrink the original row in the main table
    //                 $item->update([
    //                     'quantity' => $keepQty,
    //                     'total'    => $keepQty * $item->rate,
    //                 ]);

    //                 // Create the remainder for the new order (child table)
    //                 OrderItemsModel::create([
    //                     'order_id'     => $newOrder->id,
    //                     'product_code' => $item->product_code,
    //                     'product_name' => $item->product_name,
    //                     'rate'         => $item->rate,
    //                     'quantity'     => $moveQty,
    //                     'total'        => $moveQty * $item->rate,
    //                     'type'         => $item->type,
    //                     'remarks'      => $item->remarks,
    //                     'size'         => $item->size,
    //                 ]);
    //             } else {
    //                 // Move the entire row (if no quantity remains in the main table)
    //                 $item->update(['order_id' => $newOrder->id]);
    //             }
    //         }

    //         DB::commit();

    //         // Generate invoices for both orders
    //         $generate_order_invoice = new InvoiceController();
    //         $generate_order_invoice->generateorderInvoice($order->id, true);
    //         $generate_order_invoice->generatePackingSlip($order->id, true);

    //         $generate_order_invoice->generateorderInvoice($newOrder->id, true, false, '', true, $order->id);
    //         $generate_order_invoice->generatePackingSlip($newOrder->id, true);

    //         return response()->json([
    //             'message'        => 'Order split successfully.',
    //             'original_order' => $order->fresh('order_items'),
    //             'new_order'      => $newOrder->load('order_items'),
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error("Order split error: {$e->getMessage()}");

    //         return response()->json([
    //             'message' => 'Failed to split order.',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function splitOrder(Request $request, $id)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.product_code' => 'required|string|exists:t_order_items,product_code',
            'items.*.size' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1) Load the original order and items
            $order = OrderModel::with('order_items')
                ->findOrFail($id);

            if (!$order) {
                throw new \Exception("Order not loaded for ID: {$id}");
            }

            // 2) Build a map of product_code + size → quantity
            $keepMap = collect($data['items'])
                ->keyBy(function($item) {
                    // return $item['product_code'] . '_' . $item['size'];
                    return $item['product_code'] . '_' . ($item['size'] ?? '');
                })
                ->map(fn($i) => $i['quantity']);

            // 3) Calculate the totals before splitting the order
            $originalTotal = 0;
            $movedTotal = 0;

            foreach ($order->order_items as $item) {
                $origQty = $item->quantity;
                $key = $item->product_code . '_' . $item->size;  // Create key based on product_code + size
                $keepQty = $keepMap->get($key, 0);

                // Sum the original total and moved total
                // $originalTotal += ($keepQty * $item->rate);

                // If quantity is 0, move the entire quantity to the new order (child order)
                // if ($keepQty == 0) {
                //     $movedTotal += ($origQty * $item->rate);  // Moving full quantity to new order
                // } else {
                //     $movedTotal += ($keepQty * $item->rate);  // Only keeping the quantity in the original order
                // }
                $originalTotal += ($keepQty * $item->rate);
                $movedTotal += (($origQty - $keepQty) * $item->rate);
            }

            // 4) Generate the new order ID
            $parts = explode('/', $order->order_id);
            $parts[2] .= 'SPL';
            $newOrderCode = implode('/', $parts);

            // 5) Create the new order (child order)
            $newOrder = OrderModel::create([
                'user_id' => $order->user_id,
                'order_id' => $newOrderCode,
                'order_date' => Carbon::now(),
                'amount' => $movedTotal,
                'type' => $order->type,
            ]);

            // 6) Update the original order's amount
            $order->update(['amount' => $originalTotal]);

            // 7) Split or move the items
            foreach ($order->order_items as $item) {
                $origQty = $item->quantity;
                $key = $item->product_code . '_' . $item->size;  // Create key based on product_code + size
                $keepQty = $keepMap->get($key, 0);
                $moveQty = $origQty - $keepQty;

                // If quantity is 0, move the full quantity to the child order
                // if ($moveQty == 0 && $keepQty == 0) {
                //     // Move the full quantity to the new order (child)
                //     $item->update(['order_id' => $newOrder->id, 'quantity' => $origQty]);
                //     continue;  // Skip to the next item
                // }

                if ($keepQty == 0) {
                    $item->update(['order_id' => $newOrder->id, 'quantity' => $origQty]);
                    continue;
                }


                // If quantity is 0 for the original order, move it entirely to the child order
                if ($keepQty === 0) {
                    $item->update(['order_id' => $newOrder->id, 'quantity' => $origQty]);
                    continue;  // Skip to the next item
                }

                // If the original quantity is more than the kept quantity, split the item
                if ($keepQty > 0) {
                    // Update the original item to the kept quantity
                    $item->update([
                        'quantity' => $keepQty,
                        'total' => $keepQty * $item->rate,
                    ]);

                    // Create a new row in the child order for the remaining quantity
                    if ($moveQty > 0) {
                        OrderItemsModel::create([
                            'order_id' => $newOrder->id,
                            'product_code' => $item->product_code,
                            'product_name' => $item->product_name,
                            'rate' => $item->rate,
                            'quantity' => $moveQty,
                            'total' => $moveQty * $item->rate,
                            'type' => $item->type,
                            'remarks' => $item->remarks,
                            'size' => $item->size,
                        ]);
                    }
                } else {
                    // If no quantity remains in the original order, just move it to the child order
                    $item->update(['order_id' => $newOrder->id]);
                }
            }

            DB::commit();

            // Generate invoices for both the original and new orders
            $generate_order_invoice = new InvoiceController();
            $generate_order_invoice->generateorderInvoice($order->order_id, true);
            $generate_order_invoice->generatePackingSlip($order->order_id, true);

            $generate_order_invoice->generateorderInvoice($newOrder->id, true, false, '', true, $order->order_id);
            $generate_order_invoice->generatePackingSlip($newOrder->id, true);

            return response()->json([
                'message' => 'Order split successfully.',
                'original_order' => $order->fresh('order_items'),
                'new_order' => $newOrder->load('order_items'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order split error: {$e->getMessage()}");

            return response()->json([
                'message' => 'Failed to split order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
