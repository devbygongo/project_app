<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\CartModel;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\StockCartModel;

use App\Models\StockOrdersModel;

use App\Models\StockOrderItemsModel;

use App\Http\Controllers\InvoiceController;

use App\Utils\sendWhatsAppUtility;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

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

    public function edit_order(Request $request, $id)
    {
        // Validate incoming request data
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

        // Update the order details
        $order->amount = $request->input('amount');
        $order->save();

        // Remove existing order items for the given order ID
        OrderItemsModel::where('order_id', $id)->delete();

        // Add the updated items to the order
        $items = $request->input('items');
        foreach ($items as $item) {
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
        }

        $generate_order_invoice = new InvoiceController();
        $generate_order_invoice->generateorderInvoice($id, true);

        return response()->json([
            'message' => 'Order updated successfully!',
            'order' => $order,
            'items' => $items
        ], 200);
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


}
