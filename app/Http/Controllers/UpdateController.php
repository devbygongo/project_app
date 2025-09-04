<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\CartModel;

use App\Models\CounterModel;

use App\Models\OrderModel;

use App\Models\ProductModel;

use App\Models\OrderItemsModel;

use App\Models\StockCartModel;

use App\Models\StockOrdersModel;

use App\Models\StockOrderItemsModel;

use App\Models\LogsModel;

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

    public function generate_otp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string'],
        ]);

        $mobile = $request->input('mobile');

        if (strlen($mobile) === 15) {
            // Remove 2nd and 3rd characters
            $mobile = substr($mobile, 0, 1) . substr($mobile, 3);
        }


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
                    'amount' => ($request->input('rate')) * ($request->input('quantity')),
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

    //     $request->validate([
    //         'order_id' => 'required|string',
    //         'order_type' => 'required|string',
    //         'user_id' => 'required|integer',
    //         'amount' => 'required|numeric',
    //         'items' => 'required|array',
    //         'items.*.product_code' => 'required|string',
    //         'items.*.product_name' => 'required|string',
    //         'items.*.quantity' => 'required|integer',
    //         'items.*.orig_quantity' => 'nullable|integer',
    //         'items.*.rate' => 'required|numeric',
    //         'items.*.total' => 'required|numeric',
    //         'items.*.remarks' => 'nullable|string',
    //         'items.*.markedForDeletion' => 'nullable|boolean',
    //         'items.*.removalReason' => 'nullable|string',
    //         'cancel_order_id' => 'nullable|string',
    //     ]);

    //     $order = OrderModel::find($id);

    //     if (!$order) {
    //         return response()->json(['message' => 'Order not found!'], 404);
    //     }

    //     if ($order->user_id !== $request->input('user_id')) {
    //         return response()->json([
    //             'message' => 'Unauthorized action. This order does not belong to the specified user.'
    //         ], 403);
    //     }
        
    //     $cancelOrderIds = array_filter(array_map('trim', explode(',', $request->input('cancel_order_id'))));

    //     $is_merged = false;
    //     $merged_orders = '';
    //     DB::beginTransaction();

    //     try {
            
    //         if (!empty($cancelOrderIds)) {
    //             foreach ($cancelOrderIds as $cancelId) {
                    
    //                 $cancelOrder = OrderModel::find($cancelId);
    //                 if ($cancelOrder) {
    //                     $cancelOrder->status = "cancelled";
    //                     $cancelOrder->save();

    //                     // Append the cancelled order_id to the merged_orders string
    //                     if ($merged_orders) {
    //                         $merged_orders .= ', '; // Add a comma separator between IDs
    //                     }
    //                     $merged_orders .= $cancelOrder->order_id; // Add the current order_id
    //                 } else {
    //                     Log::warning("Cancel Order ID {$cancelId} not found");
    //                 }
    //                 $is_merged = true;
    //             }
    //         }

    //         $existingItems = OrderItemsModel::where('order_id', $id)->get()->keyBy('product_code');
    //         OrderItemsModel::where('order_id', $id)->delete();

    //         $user_id = $order->user_id;
    //         $user_type = User::select('type')->where('id', $user_id)->first();
    //         $items = $request->input('items');
    //         $calculatedAmount = 0;

    //         foreach ($items as $item) {
    //             if ($item['markedForDeletion'] ?? false) {
    //                 if (($item['removalReason'] ?? '') === 'Not in stock') {
    //                     $wishlistController = new WishlistController();
    //                     $wishlistController->saveToWishlist($user_id, $item);
    //                 }
    //                 continue;
    //             }

    //             $product = ProductModel::where('product_code', $item['product_code'])->first();
    //             if (!$product) {
    //                 throw new \Exception("Product {$item['product_code']} not found.");
    //             }

    //             OrderItemsModel::create([
    //                 'order_id' => $id,
    //                 'product_code' => $item['product_code'],
    //                 'product_name' => $item['product_name'],
    //                 'quantity' => $item['quantity'],
    //                 'rate' => $item['rate'],
    //                 'total' => $item['total'],
    //                 'type' => strtolower($request->input('order_type')),
    //                 'remarks' => $item['remarks'] ?? '',
    //             ]);

    //             $calculatedAmount += $item['total'];
    //         }

    //         // Set order amount based on sum of item totals, not input
    //         $order->amount = $calculatedAmount;
    //         $order->save();

    //         // if ($get_user->mobile != "+918961043773") {
    //             $generate_order_invoice = new InvoiceController();
    //             $generate_order_invoice->generateorderInvoice($id, true, $is_merged, $merged_orders);
    //             $generate_order_invoice->generatePackingSlip($id, true);
    //         // }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Order updated successfully!',
    //             'order' => $order,
    //             'items' => $items
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         Log::error('Failed to edit order', [
    //             'order_id' => $id,
    //             'user_id' => $get_user->id ?? null,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'message' => 'Failed to update order.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function edit_order(Request $request, $id)
    {
        $get_user = Auth::user();

        Log::info('Split order request payload', $request->all());

        if (is_numeric($request->input('order_id'))) {
            $internalId = (int) $request->input('order_id');
            $dbOrder = OrderModel::select('order_id')->where('id', $internalId)->first();
        
            if ($dbOrder) {
                $request->merge([
                    'order_id' => $dbOrder->order_id
                ]);
            } else {
                return response()->json(['message' => 'Order ID not found for numeric input.'], 404);
            }
        }
        
        Log::info('Split order request payload', $request->all());

        // try {
        //     LogsModel::create([
        //         'function'   => 'edit_order',
        //         'request'    => json_encode([
        //             'params'   => $request->all(),
        //             'order_id' => $id,
        //             'user_id'  => Auth::id(),
        //         ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        //         'created_at' => now(),
        //     ]);
        // } catch (\Throwable $e) {
        //     Log::warning('Failed to write request log (t_request_json) for edit_order', [
        //         'order_id' => $id,
        //         'error'    => $e->getMessage(),
        //     ]);
        //     // continue without failing the main operation
        // }
        
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
            'items.*.orig_quantity' => 'nullable|integer',
            'cancel_order_id' => 'nullable|string',
            'edit_type' => 'nullable|in:edit,merge,split',
        ]);

        $editType = strtolower($request->input('edit_type', 'edit'));
        $cancelOrderIds = array_filter(array_map('trim', explode(',', $request->input('cancel_order_id'))));
        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        if ($order->user_id !== $request->input('user_id')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $is_merged = false;
        $merged_orders = '';
        $is_split = false;
        $old_order_id = '';
        $newOrder = null;

        DB::beginTransaction();

        try {
            if ($editType === 'merge' && !empty($cancelOrderIds)) {
                foreach ($cancelOrderIds as $cancelId) {
                    $cancelOrder = OrderModel::find($cancelId);
                    if ($cancelOrder) {
                        $cancelOrder->status = "cancelled";
                        $cancelOrder->save();
                        $merged_orders .= ($merged_orders ? ', ' : '') . $cancelOrder->order_id;
                        $is_merged = true;
                    } else {
                        Log::warning("Cancel Order ID {$cancelId} not found");
                    }
                }
            }

            if ($editType === 'split') {

                Log::info('Split operation started', ['order_id' => $order->id]);

                $splitItems = [];
                $keepItems = [];
                $splitTotal = 0;
                $keepTotal = 0;

                foreach ($request->items as $item) {
                    Log::debug('Processing item', [
                        'product_code' => $item['product_code'],
                        'orig_quantity' => $item['orig_quantity'] ?? null,
                        'quantity' => $item['quantity'],
                        'markedForDeletion' => $item['markedForDeletion'] ?? false
                    ]);

                    $moveQty = ($item['markedForDeletion'] ?? false)
                        ? $item['orig_quantity']
                        : max(0, ($item['orig_quantity'] ?? 0) - $item['quantity']);

                    if ($moveQty > 0) {
                        $splitItems[] = array_merge($item, ['quantity' => $moveQty, 'total' => $moveQty * $item['rate']]);
                        $splitTotal += ($moveQty * $item['rate']);
                    }

                    $keptQty = $item['quantity'];
                    if ($keptQty > 0 && !($item['markedForDeletion'] ?? false)) {
                        $keepItems[] = array_merge($item, ['quantity' => $keptQty, 'total' => $keptQty * $item['rate']]);
                        $keepTotal += ($keptQty * $item['rate']);
                    }
                }

                if (count($splitItems) > 0) {
                    $baseOrderId = $order->order_id;

                    if (preg_match('/^(.*?)(SPL(\d+)?)?$/', $baseOrderId, $matches)) {
                        $base = $matches[1];
                        $suffix = isset($matches[3]) ? intval($matches[3]) + 1 : 2;
                        $newOrderCode = $base . 'SPL' . $suffix;
                    } else {
                        $newOrderCode = $baseOrderId . 'SPL2';
                    }

                    Log::info('Creating new split order', ['base_order_id' => $order->order_id]);

                    $newOrder = OrderModel::create([
                        'user_id' => $order->user_id,
                        'order_id' => $newOrderCode,
                        'order_date' => Carbon::now(),
                        'amount' => $splitTotal,
                        'type' => $order->type,
                    ]);

                    foreach ($splitItems as $item) {
                        Log::debug('Creating item in split order', ['product_code' => $item['product_code'], 'quantity' => $item['quantity']]);

                        OrderItemsModel::create([
                            'order_id' => $newOrder->id,
                            'product_code' => $item['product_code'],
                            'product_name' => $item['product_name'],
                            'rate' => $item['rate'],
                            'quantity' => $item['quantity'],
                            'total' => $item['total'],
                            'type' => strtolower($request->input('order_type')),
                            'remarks' => $item['remarks'] ?? '',
                            'size' => $item['size'] ?? null,
                        ]);
                    }

                    $is_split = true;
                    $old_order_id = $order->order_id;

                    Log::info('Updating original order amount', ['keep_total' => $keepTotal]);

                    $order->update(['amount' => $keepTotal]);
                    $request->merge(['items' => $keepItems]);
                }
            }

            OrderItemsModel::where('order_id', $id)->delete();
            $calculatedAmount = 0;

            foreach ($request->items as $item) {
                if (($item['markedForDeletion'] ?? false) && $editType !== 'split') {
                    if (($item['removalReason'] ?? '') === 'Not in stock') {
                        (new WishlistController)->saveToWishlist($order->user_id, $item);
                    }
                    continue;
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
                    'size' => $item['size'] ?? null,
                ]);

                $calculatedAmount += $item['total'];
            }

            $order->amount = $calculatedAmount;
            $order->save();

            $invoiceController = new InvoiceController();
            $invoiceController->generateorderInvoice($order->id, [
                'is_edited' => true,
                'is_merged' => $is_merged,
                'merged_orders' => $merged_orders,
                'is_split' => false,
                'old_order_id' => ''
            ]);
            $invoiceController->generatePackingSlip($order->id, true);

            if ($is_split && $newOrder) {
                $invoiceController->generateorderInvoice($newOrder->id, [
                    'is_edited' => true,
                    'is_split' => true,
                    'old_order_id' => $old_order_id,
                ]);
                $invoiceController->generatePackingSlip($newOrder->id, true);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully!',
                'order' => $order->fresh('order_items'),
                'new_order' => $newOrder ?? null,
            ]);

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

    // public function complete_order_stock(Request $request, $id)
    // {

    //     // Find the order by its ID
    //     $order = OrderModel::find($id);

    //     if (!$order) {
    //         return response()->json([
    //             'message' => 'Order not found!'
    //         ], 404);
    //     }

    //     // ✅ Save request details into t_request_json
    //     LogsModel::create([
    //         'function'   => 'complete_order_stock',
    //         'request'    => json_encode([
    //             'params' => $request->all(),   // Request data
    //             'order_id' => $id,             // Explicit order id
    //             'user_id' => Auth::id()        // Who triggered it (optional, if Auth is used)
    //         ]),
    //         'created_at' => now(),
    //     ]);

    //     // Update the status of the order to 'completed'
    //     // $order->status = 'completed';
    //     // $order->save();

    //     return response()->json([
    //         'message' => 'Order status updated to completed successfully!',
    //         'order' => $order
    //     ], 200);
    // }

    public function complete_order_stock(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = OrderModel::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found!'], 404);
            }

            // Log the raw request
            LogsModel::create([
                'function'   => 'complete_order_stock',
                'request'    => json_encode([
                    'params'  => $request->all(),
                    'order_id'=> $id,
                    'user_id' => Auth::id()
                ]),
                'created_at' => now(),
            ]);

            // Mark completed
            $order->status = 'completed';
            $order->save();

            // Atomic counter fetcher
            $nextOrderId = function () {
                $counter = CounterModel::where('name', 'stock_order')->lockForUpdate()->firstOrFail();
                $oid = $counter->prefix . str_pad($counter->counter, 5, '0', STR_PAD_LEFT) . $counter->postfix;
                $counter->increment('counter');
                return $oid;
            };

            // Helper: match "DIRECT" godown variants safely
            $isDirect = function (?string $name): bool {
                if (!$name) return false;
                $n = mb_strtoupper(trim($name));
                return $n === 'DIRECT DISPATCH' || $n === 'DIRECT GODOWN' || str_starts_with($n, 'DIRECT');
            };

            // Optionally resolve DIRECT godown id (if you maintain one)
            $directGodownId = null;
            if (class_exists(\App\Models\GodownModel::class)) {
                $directGodownId = \App\Models\GodownModel::whereIn('name', ['DIRECT DISPATCH','DIRECT GODOWN'])->value('id');
            }

            // ===== Aggregation =====
            // OUT: all allocations, grouped by product|size|godown
            // IN : only DIRECT allocations, grouped by product|size  (single DD line per SKU/size)
            $outAgg = []; // key: product|size|godown_id
            $inDDAgg = []; // key: product|size

            $items = $request->input(); // expected: [ { product_code, product_name, size?, allocations:[{godown_id, godown_name, qty}] }, ... ]
            foreach ($items as $item) {
                $pcode = $item['product_code'];
                $pname = $item['product_name'];
                $size  = $item['size'] ?? null;

                foreach ($item['allocations'] as $a) {
                    $qty   = (float) ($a['qty'] ?? 0);
                    if ($qty <= 0) continue;
                    $gid   = $a['godown_id'] ?? null;
                    $gname = $a['godown_name'] ?? '';

                    // OUT (always, split by godown)
                    $keyOut = $pcode.'|'.($size ?? '').'|'.($gid ?? '0');
                    if (!isset($outAgg[$keyOut])) {
                        $outAgg[$keyOut] = [
                            'product_code' => $pcode,
                            'product_name' => $pname,
                            'size'         => $size,
                            'godown_id'    => $gid,
                            'quantity'     => 0,
                        ];
                    }
                    $outAgg[$keyOut]['quantity'] += $qty;

                    // IN (only DIRECT xfers)
                    if ($isDirect($gname)) {
                        $keyIn = $pcode.'|'.($size ?? '');
                        if (!isset($inDDAgg[$keyIn])) {
                            $inDDAgg[$keyIn] = [
                                'product_code' => $pcode,
                                'product_name' => $pname,
                                'size'         => $size,
                                'godown_id'    => $directGodownId ?? $gid, // prefer canonical DIRECT id
                                'quantity'     => 0,
                            ];
                        }
                        $inDDAgg[$keyIn]['quantity'] += $qty;
                    }
                }
            }

            // ===== Create OUT order (all godowns) =====
            $stockOrderOut = null;
            if (!empty($outAgg)) {
                $outId = $nextOrderId();
                $stockOrderOut = StockOrdersModel::create([
                    'order_id'   => $outId,
                    'user_id'    => Auth::id(),
                    'order_date' => now(),
                    'type'       => 'OUT',
                    't_order_id' => $id,
                    'pdf'        => null,
                    'remarks'    => "{$order->order_id}",
                ]);

                foreach ($outAgg as $row) {
                    StockOrderItemsModel::create([
                        'stock_order_id' => $stockOrderOut->id,
                        'product_code'   => $row['product_code'],
                        'product_name'   => $row['product_name'],
                        'godown_id'      => $row['godown_id'],
                        'quantity'       => $row['quantity'],
                        'type'           => 'OUT',
                        'size'           => $row['size'],
                    ]);
                }
            }

            // ===== Create IN order (DIRECT only) =====
            $stockOrderIn = null;
            if (!empty($inDDAgg)) {
                $inId = $nextOrderId();
                $stockOrderIn = StockOrdersModel::create([
                    'order_id'   => $inId,
                    'user_id'    => Auth::id(),
                    'order_date' => now(),
                    'type'       => 'IN',
                    't_order_id' => $id,
                    'pdf'        => null,
                    'remarks'    => "{$order->order_id}",
                ]);

                foreach ($inDDAgg as $row) {
                    StockOrderItemsModel::create([
                        'stock_order_id' => $stockOrderIn->id,
                        'product_code'   => $row['product_code'],
                        'product_name'   => $row['product_name'],
                        'godown_id'      => $row['godown_id'], // DIRECT godown id
                        'quantity'       => $row['quantity'],
                        'type'           => 'IN',
                        'size'           => $row['size'],
                    ]);
                }
            }

            // Generate PDFs after items are created
            $invoice = new InvoiceControllerZP();
            if ($stockOrderOut) {
                $stockOrderOut->pdf = $invoice->generatestockorderInvoice($stockOrderOut->id);
                $stockOrderOut->save();
            }
            if ($stockOrderIn) {
                $stockOrderIn->pdf = $invoice->generatestockorderInvoice($stockOrderIn->id);
                $stockOrderIn->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Done. OUT has all items split by godown; IN has only DIRECT quantities.',
                'order'   => $order,
                'stock_orders' => [
                    'out' => $stockOrderOut ? $stockOrderOut->order_id : null,
                    'in'  => $stockOrderIn ? $stockOrderIn->order_id : null,
                ],
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('complete_order_stock failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to complete order stock.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
                    'size' => 'nullable',
                ]);

                // Update the stock cart item
                $stockCartItem->update([
                    'product_code' => $validated['product_code'],
                    'product_name' => $validated['product_name'],
                    'quantity' => $validated['quantity'],
                    'godown_id' => $validated['godown_id'],
                    'type' => $validated['type'],
                    'size' => $validated['size'] ?? null,
                ]);

                // Return the success response
                return response()->json([
                    'message' => 'Stock cart item updated successfully.',
                    'data' => $stockCartItem->makeHidden(['id', 'updated_at', 'created_at']),
                ], 200);
            })();
    }

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
                'items.*.size' => 'nullable',
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
                    'size' => $item['size'] ?? null,
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
}
