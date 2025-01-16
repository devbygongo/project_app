<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemsModel;
use App\Models\StockOrdersModel;
use App\Models\StockOrderItemsModel;
use Mpdf\Mpdf;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;

ini_set('memory_limit', '512M'); // Adjust as needed
set_time_limit(300); // Increase timeout to 5 minutes or as needed


class InvoiceControllerZP extends Controller
{
    //
    public function generateorderInvoiceZP($orderId, $is_edited = false)
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
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks', 'type')
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
		$headerHtml = view('order_invoice_template_header_zp', ['user' => $user, 'order' => $order])->render();
		$mpdf->WriteHTML($headerHtml);

		$chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

		foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('order_invoice_template_items_zp', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			ob_flush();
			flush();
		}


		// Render the footer
		$footerHtml = view('order_invoice_template_footer_zp', ['order' => $order])->render();
		$mpdf->WriteHTML($footerHtml);

		// Output the PDF
		$publicPath = 'uploads/orders/';
		$fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
		$filePath = storage_path('app/public/' . $publicPath . $fileName);

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
                                    'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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
                if($mobileNumber == '+918961043773' || true)
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
                                    'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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
                                    'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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
                if($mobileNumber == '+918961043773' || true)
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
                                    'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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

    // for stock orders
    // public function generatestockorderInvoice($orderId, $is_edited = false)
    // {
    //     // $get_user = Auth::id();
        
    //     // $stock_order = StockOrdersModel::select('order_id','order_date',)
    //     //                                 ->where('id', $orderId)
    //     //                                 ->first();

    //     $stock_order = StockOrdersModel::with('user:name,id')
    //                                     ->select('order_id', 'order_date', 'remarks', 'user_id')
    //                                     ->where('id', $orderId)
    //                                     ->first();

    //     // \DB::enableQueryLog(); // Enable query logging
    //     $stock_order_items = StockOrderItemsModel::with(['stock_product:product_code,product_image', 'godown:name,id'])
    //                                             ->select('product_code', 'product_name', 'quantity','type', 'godown_id')
    //                                             ->where('stock_order_id', $orderId)
    //                                             ->get();

    //         // Fetch and dump the logged queries
    //         // $queries = \DB::getQueryLog();
    //         // dd($queries);

    //     $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();
        

    //     if (!$stock_order || $stock_order_items->isEmpty()) {
    //         return response()->json(['error' => 'Sorry, required data are not available!'], 500);
    //     }

    //     $stock_sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', trim($stock_order->order_id));
    //     $stock_sanitizedOrderId = trim($stock_sanitizedOrderId, '-');

	// 	//die($sanitizedOrderId);
		
    //     $stock_data = [
    //         'order' => $stock_order,
    //         'order_items' => $stock_order_items,
    //     ];
	// 	$mpdf = new Mpdf();

	// 	$stock_headerHtml = view('stock_order_invoice_template_header', ['order' => $stock_order])->render();
	// 	$mpdf->WriteHTML($stock_headerHtml);

	// 	$stock_chunkSize = 10;
	// 	$stock_orderItems = collect($stock_order_items)->chunk($stock_chunkSize);

	// 	foreach ($stock_orderItems as $stock_chunk) {
	// 		foreach ($stock_chunk as $stock_index => $stock_item) {

    //             // dd($stock_item->godown->name);
    //             // // Access individual item details
    //             // $product = $stock_item->stock_product; // Related product data
    //             // $productCode = $product->product_code ?? 'N/A'; // Handle null case
    //             // $productImage = $product->product_image ?? '/default_image_path.jpg'; // Default image if not found
	// 			// Render each item row individually
	// 			// $stock_htmlChunk = view('stock_order_invoice_template_items', compact('stock_item', 'stock_index'))->render();
    //             $stock_htmlChunk = view('stock_order_invoice_template_items', [
    //                 'stock_item' => $stock_item,
    //                 'stock_index' => $stock_index,
    //                 'godown_name' => $stock_item->godown->name ?? 'N/A', // Add godown_name here
    //             ])->render();
	// 			$mpdf->WriteHTML($stock_htmlChunk);
	// 		}
	// 		ob_flush();
	// 		flush();
	// 	}


	// 	// Render the footer
	// 	$stock_footerHtml = view('stock_order_invoice_template_footer', ['order' => $stock_order])->render();
	// 	$mpdf->WriteHTML($stock_footerHtml);

	// 	// Output the PDF
	// 	$stock_publicPath = 'uploads/stock/';
	// 	$stock_fileName = 'invoice_' . $stock_sanitizedOrderId . '.pdf';
	// 	$stock_filePath = storage_path('app/public/' . $stock_publicPath . $stock_fileName);

	// 	if (!File::isDirectory($storage_path = storage_path('app/public/' . $stock_publicPath))) {
	// 		File::makeDirectory($storage_path, 0755, true);
	// 	}

	// 	$mpdf->Output($stock_filePath, 'F');


    //     $stock_fileUrl = asset('storage/' . $stock_publicPath . $stock_fileName);

    //     $StockOrdersModelupdate_order = StockOrdersModel::where('id', $orderId)
    //     ->update([
    //         'pdf' => $stock_fileUrl,
    //     ]);

    //     // Directly create an instance of SendWhatsAppUtility
    //     $whatsAppUtility = new sendWhatsAppUtility();

    //     if(!$is_edited)
    //     {
    //         $templateParams = [
    //             'name' => 'ace_new_order_admin', // Replace with your WhatsApp template name
    //             'language' => ['code' => 'en'],
    //             'components' => [
    //                 [
    //                     'type' => 'header',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'document',
    //                             'document' => [
    //                                 'link' =>  $stock_fileUrl, // Replace with the actual URL to the PDF document
    //                                 'filename' => $stock_sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
    //                             ]
    //                         ]
    //                     ]
    //                 ],[
    //                     'type' => 'body',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'text',
    //                             'text' => 'Stock Entry',
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' =>  '-',
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => $stock_order->order_id,
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => Carbon::now()->format('d-m-Y'),
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => '-',
    //                         ],
    //                     ],
    //                 ]
    //             ],
    //         ];

    //         foreach ($mobileNumbers as $mobileNumber) 
    //         {
    //             if($mobileNumber == '+918961043773')
    //             {
    //                 // Send message for each number
    //                 $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

    //                 // Check if the response has an error or was successful
    //                 if (isset($responseArray['error'])) 
    //                 {
    //                     echo "Failed to send order to Whatsapp!";
    //                 }
    //             }
    //         }

    //     }else{
    //         $templateParams = [
    //             'name' => 'ace_edit_order_admin', // Replace with your WhatsApp template name
    //             'language' => ['code' => 'en'],
    //             'components' => [
    //                 [
    //                     'type' => 'header',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'document',
    //                             'document' => [
    //                                 'link' =>  $stock_fileUrl, // Replace with the actual URL to the PDF document
    //                                 'filename' => $stock_sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
    //                             ]
    //                         ]
    //                     ]
    //                 ],[
    //                     'type' => 'body',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'text',
    //                             'text' => 'Stock Entry',
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' =>  '-',
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => $stock_order->order_id,
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => Carbon::parse($order->order_date)->format('d-m-Y'),
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => '-',
    //                         ],
    //                     ],
    //                 ]
    //             ],
    //         ];

    //         foreach ($mobileNumbers as $mobileNumber) 
    //         {
    //             if($mobileNumber == '+918961043773' || true)
    //             {
    //                 // Send message for each number
    //                 $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

    //                 // Check if the response has an error or was successful
    //                 if (isset($responseArray['error'])) 
    //                 {
    //                     echo "Failed to send order to Whatsapp!";
    //                 }
    //             }
    //         }

    //         $templateParams = [
    //             'name' => 'ace_edit_order_user', // Replace with your WhatsApp template name
    //             'language' => ['code' => 'en'],
    //             'components' => [
    //                 [
    //                     'type' => 'header',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'document',
    //                             'document' => [
    //                                 'link' =>  $stock_fileUrl, // Replace with the actual URL to the PDF document
    //                                 'filename' => $stock_sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
    //                             ]
    //                         ]
    //                     ]
    //                 ],[
    //                     'type' => 'body',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'text',
    //                             'text' => 'Stock Entry',
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => $stock_order->order_id,
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => Carbon::parse($order->order_date)->format('d-m-Y'),
    //                         ],
    //                         [
    //                             'type' => 'text',
    //                             'text' => '-',
    //                         ],
    //                     ],
    //                 ]
    //             ],
    //         ];

    //         $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
    //     }
    
    //     // // Assuming additional functionality such as WhatsApp integration etc.
    //     // return $mpdf->Output('invoice.pdf', 'I');
    //     return $stock_fileUrl;
    // }

    public function generatestockorderInvoice($orderId, $is_edited = false)
    {
        try {
            // Fetch the stock order and related items
            $stockOrder = StockOrdersModel::with([
                'user:id,name',
                'items.stock_product:product_code,product_image',
                'items.godown:name,id',
            ])
            ->select('id', 'order_id', 'order_date', 'remarks', 'user_id')
            ->find($orderId);

            if (!$stockOrder || $stockOrder->items->isEmpty()) {
                return response()->json(['error' => 'Sorry, required data are not available!'], 404);
            }

            // Generate sanitized order ID for filename
            $sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', $stockOrder->order_id);
            $sanitizedOrderId = trim($sanitizedOrderId, '-');

            // Prepare data for PDF generation
            $pdf = new \Mpdf\Mpdf();
            $headerHtml = view('stock_order_invoice_template_header', ['order' => $stockOrder])->render();
            $pdf->WriteHTML($headerHtml);

            $chunkSize = 10;
            $orderItems = $stockOrder->items->chunk($chunkSize);

            foreach ($orderItems as $chunk) {
                foreach ($chunk as $index => $item) {
                    $htmlChunk = view('stock_order_invoice_template_items', [
                        'stock_item' => $item,
                        'stock_index' => $index,
                        'godown_name' => $item->godown->name ?? 'N/A',
                    ])->render();
                    $pdf->WriteHTML($htmlChunk);
                }
            }

            $footerHtml = view('stock_order_invoice_template_footer', ['order' => $stockOrder])->render();
            $pdf->WriteHTML($footerHtml);

            // Save the PDF file
            $storagePath = storage_path('app/public/uploads/stock/');
            $fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
            $filePath = $storagePath . $fileName;

            if (!File::isDirectory($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            $pdf->Output($filePath, 'F');
            $fileUrl = asset('storage/uploads/stock/' . $fileName);

            // Update the stock order with the PDF URL
            $stockOrder->update(['pdf' => $fileUrl]);

            // WhatsApp notifications
            // $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();
            // $whatsAppUtility = new sendWhatsAppUtility();
            // $templateName = $is_edited ? 'ace_edit_order_admin' : 'ace_new_order_admin';
            // $templateParams = $this->prepareWhatsAppTemplate($templateName, $fileUrl, $stockOrder, $is_edited);

            // foreach ($mobileNumbers as $mobileNumber) {
            //     if ($mobileNumber == '+918961043773' || $is_edited) {
            //         $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');
            //         if (isset($response['error'])) {
            //             echo "Failed to send order to WhatsApp!";
            //         }
            //     }
            // }

            // Send WhatsApp notification to the user
            // if ($is_edited) {
            //     $userTemplateParams = $this->prepareWhatsAppTemplate('ace_edit_order_user', $fileUrl, $stockOrder, true);
            //     $response = $whatsAppUtility->sendWhatsApp($stockOrder->user->mobile, $userTemplateParams, '', 'User Order Invoice');
            // }

            return $fileUrl;
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while generating the invoice.', 'details' => $e->getMessage()], 500);
        }
    }

}
