<?php


class MiscController extends Controller
{
    
    public function new_orders(Request $request)
    {
        $get_user = Auth::User();

        if($get_user->role == 'user') {
            $userId = $get_user->id;
        } else {
            $request->validate([
                'user_id' => 'required',
            ]);
            $userId = $request->input('user_id');
        }

        $current_user = User::select('type')->where('id', $userId)->first();
        $user_type = $current_user->type;

        $orders_data = [];
        $invoice_queue = [];

        if ($user_type == 'zeroprice') {

            $get_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate', 'type', 'remarks')
                ->where('user_id', $userId)
                ->get();

            $get_counter = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order_zeroprice')->first();

            if ($get_counter && count($get_product) > 0) {

                $get_order_id = $get_counter->prefix . $get_counter->counter . $get_counter->postfix;

                $amount_total = 0;
                foreach ($get_product as $product) {
                    $amount_total += (($product->rate) * ($product->quantity));
                }

                $create_order = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $amount_total,
                    'type' => 'basic',
                ]);

                foreach ($get_product as $product) {
                    OrderItemsModel::create([
                        'order_id' => $create_order->id,
                        'product_code' => $product->product_code,
                        'product_name' => $product->product_name,
                        'rate' => $product->rate,
                        'quantity' => $product->quantity,
                        'total' => $product->rate * $product->quantity,
                        'type' => $product->type,
                        'remarks' => $product->remarks,
                    ]);
                }

                CounterModel::where('name', 'order_zeroprice')
                    ->update(['counter' => ($get_counter->counter + 1)]);

                $orders_data[] = [
                    'order_id' => $create_order->order_id,
                    'amount' => $create_order->amount,
                    'type' => $create_order->type,
                    'order_date' => $create_order->order_date,
                ];

                $invoice_queue[] = [
                    'type' => 'zeroprice',
                    'order_table_id' => $create_order->id
                ];

                CartModel::where('user_id', $userId)->delete();
            }

        } else {
            $create_order_basic = null;
            $create_order_gst = null;

            // BASIC PRODUCTS
            $get_basic_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate', 'type', 'remarks')
                ->where('user_id', $userId)
                ->where('type', 'basic')
                ->get();

            $get_counter_basic = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order_basic')->first();

            if ($get_counter_basic && count($get_basic_product) > 0) {
                $get_order_id = $get_counter_basic->prefix . $get_counter_basic->counter . $get_counter_basic->postfix;

                $basic_amount_total = 0;
                foreach ($get_basic_product as $basic_product) {
                    $basic_amount_total += (($basic_product->rate) * ($basic_product->quantity));
                }

                $create_order_basic = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now(),
                    'amount' => $basic_amount_total,
                    'type' => 'basic',
                ]);

                foreach ($get_basic_product as $basic_product) {
                    OrderItemsModel::create([
                        'order_id' => $create_order_basic->id,
                        'product_code' => $basic_product->product_code,
                        'product_name' => $basic_product->product_name,
                        'rate' => $basic_product->rate,
                        'quantity' => $basic_product->quantity,
                        'total' => $basic_product->rate * $basic_product->quantity,
                        'type' => $basic_product->type,
                        'remarks' => $basic_product->remarks,
                    ]);
                }

                CounterModel::where('name', 'order_basic')
                    ->update(['counter' => ($get_counter_basic->counter + 1)]);

                $orders_data[] = [
                    'order_id' => $create_order_basic->order_id,
                    'amount' => $create_order_basic->amount,
                    'type' => $create_order_basic->type,
                    'order_date' => $create_order_basic->order_date,
                ];

                $invoice_queue[] = [
                    'type' => 'basic',
                    'order_table_id' => $create_order_basic->id
                ];
            }

            // GST PRODUCTS
            $get_gst_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate', 'type', 'remarks')
                ->where('user_id', $userId)
                ->where('type', 'gst')
                ->get();

            $get_counter_gst = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order_gst')->first();

            if ($get_counter_gst && count($get_gst_product) > 0) {
                $get_order_id = $get_counter_gst->prefix . $get_counter_gst->counter . $get_counter_gst->postfix;

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

                foreach ($get_gst_product as $gst_product) {
                    OrderItemsModel::create([
                        'order_id' => $create_order_gst->id,
                        'product_code' => $gst_product->product_code,
                        'product_name' => $gst_product->product_name,
                        'rate' => $gst_product->rate,
                        'quantity' => $gst_product->quantity,
                        'total' => $gst_product->rate * $gst_product->quantity,
                        'type' => $gst_product->type,
                        'remarks' => $gst_product->remarks,
                    ]);
                }

                CounterModel::where('name', 'order_gst')
                    ->update(['counter' => ($get_counter_gst->counter + 1)]);

                $orders_data[] = [
                    'order_id' => $create_order_gst->order_id,
                    'amount' => $create_order_gst->amount,
                    'type' => $create_order_gst->type,
                    'order_date' => $create_order_gst->order_date,
                ];

                $invoice_queue[] = [
                    'type' => 'gst',
                    'order_table_id' => $create_order_gst->id
                ];
            }

            CartModel::where('user_id', $userId)->delete();
        }

        // Respond immediately
        $hardcodedMobile = '+918777623806'; // <-- update this number

        // Generate invoice and send to WhatsApp in background
        // dispatch(function () use ($invoice_queue, $hardcodedMobile) {
        //     foreach ($invoice_queue as $item) {
        //         if ($item['type'] === 'zeroprice') {
        //             $pdf = (new InvoiceControllerZP())->new_generateorderInvoice($item['order_table_id']);
        //             // sendInvoiceToWhatsApp($hardcodedMobile, $pdf, $item['order_table_id']);
        //             $whatsAppUtility = new \App\Utilities\sendWhatsAppUtility();
        //             $whatsAppUtility->sendWhatsApp($hardcodedMobile, $pdf, $item['order_table_id']);
        //         } else {
        //             $pdf = (new InvoiceController())->new_generateorderInvoice($item['order_table_id']);
        //             // Optionally also generate packing slip:
        //             $packing_slip = (new InvoiceController())->new_generatePackingSlip($item['order_table_id']);
        //             // sendInvoiceToWhatsApp($hardcodedMobile, $pdf, $item['order_table_id']);
        //             $whatsAppUtility = new \App\Utilities\sendWhatsAppUtility();
        //             $whatsAppUtility->sendWhatsApp($hardcodedMobile, $pdf, $item['order_table_id']);
        //             // Optionally send packing slip too
        //         }
        //     }
        // })->afterResponse();

        dispatch(function () use ($invoice_queue, $hardcodedMobile) {
            foreach ($invoice_queue as $item) {
                if ($item['type'] === 'zeroprice') {
                    $pdf = (new InvoiceControllerZP())->new_generateorderInvoice($item['order_table_id']);
                    // Call WhatsApp Utility here with your parameters
                    sendWhatsAppUtility::sendWhatsApp($hardcodedMobile, $pdf, $item['order_table_id'], 'zeroprice_order'); // <-- update $pdf/$params as needed
                } else {
                    $pdf = (new InvoiceController())->new_generateorderInvoice($item['order_table_id']);
                    // Optionally generate packing slip (as you do)
                    $packing_slip = (new InvoiceController())->new_generatePackingSlip($item['order_table_id']);
                    // Call WhatsApp Utility
                    // sendWhatsAppUtility::sendWhatsApp($hardcodedMobile, $pdf, $item['order_table_id'], 'normal_order');
                    $response = sendWhatsAppUtility::sendWhatsApp($hardcodedMobile, $pdf, $item['order_table_id'], 'normal_order');
                    \Log::info("WhatsApp Utility called for order {$item['order_table_id']}, response: {$response}");
                }
            }
        })->afterResponse();

        if (!empty($orders_data)) {
            return response()->json([
                'message' => 'Order created successfully! Invoice will be generated and sent to WhatsApp shortly.',
                'data' => $orders_data
            ], 201);
        } else {
            return response()->json([
                'message' => 'Sorry, failed to create order!',
                'data' => 'Error!'
            ], 400);
        }
    }

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

    public function new_generateorderInvoice($orderId, $is_edited = false)
    {
        // $get_user = Auth::id();
        
        $order = OrderModel::select('user_id','order_id', 'amount', 'order_date','type')
                            ->where('id', $orderId)
                            ->first();
		
		

        $get_user = $order->user_id;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id', $get_user)
                    ->first();
		

        $order_items = OrderItemsModel::with('product:product_code')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();
        $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();
        

        if (!$user || !$order || $order_items->isEmpty()) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }

        $sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', trim($order->order_id));
        $sanitizedOrderId = trim($sanitizedOrderId, '-');

		//die($sanitizedOrderId);
		
        $data = [
            'user' => $user,
            'order' => $order,
            'order_items' => $order_items,
        ];
		$mpdf = new Mpdf();

        /*$html = view('order_invoice_template', $data)->render();

        
        $mpdf->WriteHTML($html);

        $publicPath = 'uploads/orders/';
        $fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        $mpdf->Output($filePath, 'F');*/
		
		// Load initial HTML for header and customer information.
		// Render the header
		$headerHtml = view('order_invoice_template_header', ['user' => $user, 'order' => $order])->render();
		$mpdf->WriteHTML($headerHtml);

		$chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

		// foreach ($orderItems as $chunk) {
		// 	foreach ($chunk as $index => $item) {
		// 		// Render each item row individually
		// 		$htmlChunk = view('order_invoice_template_items', compact('item', 'index'))->render();
		// 		$mpdf->WriteHTML($htmlChunk);
		// 	}
		// 	ob_flush();
		// 	flush();
		// }
        foreach ($orderItems as $chunk) {
            foreach ($chunk as $index => $item) {
                // Render each item row individually
                $htmlChunk = view('order_invoice_template_items', compact('item', 'index'))->render();
                $mpdf->WriteHTML($htmlChunk);
            }
            // if (ob_get_level() > 0) {
            //     ob_flush();
            //     flush();
            // }
        }

		// Render the footer
		$footerHtml = view('order_invoice_template_footer', ['order' => $order])->render();
		$mpdf->WriteHTML($footerHtml);

		// Output the PDF
		$publicPath = 'uploads/orders/';
		$fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
		$filePath = storage_path('app/public/' . $publicPath . $fileName);

        // Check if the file already exists and delete it
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

		if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
			File::makeDirectory($storage_path, 0755, true);
		}

		$mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        $update_order = OrderModel::where('id', $orderId)
        ->update([
            'order_invoice' => $fileUrl,
        ]);

        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();

        if(!$is_edited)
        {
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ace_new_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => Carbon::now()->format('d-m-Y'),
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
                if($mobileNumber != '+918777623806')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }

            $templateParams = [
                'name' => 'ace_new_order_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
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
                                'text' => Carbon::now()->format('d-m-Y'),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->amount,
                            ],
                        ],
                    ]
                ],
            ];

            $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
        }else{
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ace_edit_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
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
                if($mobileNumber != '+918777623806')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }

            $templateParams = [
                'name' => 'ace_edit_order_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
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

            $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
        }
    
        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
    }

    public function new_generatePackingSlip($orderId, $is_edited = false)
    {
        $order = OrderModel::select('user_id','order_id', 'amount', 'order_date','type')
                            ->where('id', $orderId)
                            ->first();

        $get_user = $order->user_id;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id', $get_user)
                    ->first();
		

        $order_items = OrderItemsModel::with('product:product_code')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();
        $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();
        

        if (!$user || !$order || $order_items->isEmpty()) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }

        $sanitizedUserName = preg_replace('/[^A-Za-z0-9]+/', '-', trim($user->name));
        $sanitizedUserId = trim($sanitizedUserName, '-');

		//die($sanitizedOrderId);
		
        $data = [
            'user' => $user,
            'order' => $order,
            'order_items' => $order_items,
        ];
		$mpdf = new Mpdf();

		// Render the header
		$headerHtml = view('packing_slip_template_header', ['user' => $user, 'order' => $order])->render();
		$mpdf->WriteHTML($headerHtml);

		$chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

		foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('packing_slip_template_items', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			// ob_flush();
			// flush();
		}

		// Render the footer
		$footerHtml = view('packing_slip_template_footer', ['order' => $order])->render();
		$mpdf->WriteHTML($footerHtml);

		// Output the PDF
		$publicPath = 'uploads/packing_slip/';
		$fileName = 'ps_' . $sanitizedUserId . '.pdf';
		$filePath = storage_path('app/public/' . $publicPath . $fileName);

        // Check if the file already exists and delete it
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

		if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
			File::makeDirectory($storage_path, 0755, true);
		}

		$mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        $update_order = OrderModel::where('id', $orderId)
        ->update([
            'packing_slip' => $fileUrl,
        ]);

        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();

        if(!$is_edited)
        {
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ace_new_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedUserId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => Carbon::now()->format('d-m-Y'),
                            ],
                            [
                                'type' => 'text',
                                'text' => '0',
                            ],
                        ],
                    ]
                ],
            ];

            foreach ($mobileNumbers as $mobileNumber) 
            {
                if($mobileNumber == '+918777623806')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }
        }else{
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ace_edit_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedUserId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
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
                                'text' => '0',
                            ],
                        ],
                    ]
                ],
            ];

            foreach ($mobileNumbers as $mobileNumber) 
            {
                if($mobileNumber == '+918777623806')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }
        }
    
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
    }

    // Helper function (update this with your WhatsApp logic)
    // function sendInvoiceToWhatsApp($mobile, $pdf, $orderId) {
    //     // Implement your actual WhatsApp sending logic here
    //     \Log::info("Invoice for order {$orderId} sent to WhatsApp: {$mobile} (PDF: {$pdf})");
    // }
    //
}

?>