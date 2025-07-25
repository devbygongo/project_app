<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemsModel;
use Mpdf\Mpdf;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;

ini_set('memory_limit', '512M'); // Adjust as needed
set_time_limit(300); // Increase timeout to 5 minutes or as needed


class InvoiceController extends Controller
{
    //
    public function generateorderInvoice($orderId, $is_edited = false, $is_merged = false, $merged_orders = '')
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

		foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('order_invoice_template_items', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			ob_flush();
			flush();
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
                if($mobileNumber != '+919951263652')
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
            if(!$is_merged)
            {
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
                    if($mobileNumber != '+919951263652')
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
            }else{
                $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
                $templateParams = [
                    'name' => 'ace_merged_order_admin', // Replace with your WhatsApp template name
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
                                    'text' => $merged_orders,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $order->order_id,
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
                    if($mobileNumber != '+919951263652')
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
                    'name' => 'ace_merged_order_user', // Replace with your WhatsApp template name
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
                                    'text' => $merged_orders,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $order->order_id,
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
        }
    
        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
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

    public function generatePackingSlip($orderId, $is_edited = false)
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
			ob_flush();
			flush();
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
                if($mobileNumber == '+919951263652')
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
                if($mobileNumber == '+919951263652')
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

    public function generatePackingSlipsForAllOrders()
    {
        // Fetch orders with 'pending' or 'completed' status
        $orders = OrderModel::whereIn('status', ['pending', 'completed'])  // Adjust the status values as needed
                            ->whereNull('packing_slip')
                            ->orderBy('id', 'desc') 
                            ->get();

        // Loop through each order and generate the packing slip
        foreach ($orders as $order) {
            // Call the generatePackingSlip function for each order
            $this->generatePackingSlip($order->id);
        }

        return response()->json(['message' => 'Packing slips generated for all pending and completed orders.'], 200);
    }

    public function generateInvoice($invoiceId)
    {

        $invoice_user = InvoiceModel::with('user:id,name,mobile,email,address_line_1,address_line_2,gstin')
                            ->select('order_id', 'invoice_number', 'date', 'amount', 'type', 'user_id')
                            ->where('id', $invoiceId)
                            ->first();


        $invoice_items = InvoiceItemsModel::select('product_code', 'product_name', 'rate', 'quantity', 'total')
                                    ->where('invoice_id', $invoiceId)
                                    ->get();    

        if (!isset($invoice_user) || !isset($invoice_user->user) || $invoice_items->isEmpty()) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }

        $sanitizedInvoiceNumber = preg_replace('/[^A-Za-z0-9]+/', '-', trim($invoice_user->invoice_number));
        $sanitizedInvoiceNumber = trim($sanitizedInvoiceNumber, '-');

        $data = [
            'user' => $invoice_user->user,
            'invoice' => $invoice_user,
            'invoice_items' => $invoice_items,
        ];

        $html = view('invoice_template', $data)->render();

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);

        $publicPath = 'uploads/invoices/';
        $fileName = 'invoice_' . $sanitizedInvoiceNumber . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        $mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        $update_order = InvoiceModel::where('id', $invoiceId)
        ->update([
            'invoice_file' => $fileUrl,
        ]);

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
                                'filename' => $sanitizedInvoiceNumber.'.pdf' // Optional: Set a custom file name for the PDF document
                            ]
                        ]
                    ]
                ],[
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $invoice_user->user->name,
                        ],
                        [
                            'type' => 'text',
                            'text' => $invoice_user->invoice_number,
                        ],
                        [
                            'type' => 'text',
                            'text' => Carbon::now()->format('d-m-Y'),
                        ],
                        [
                            'type' => 'text',
                            'text' => $invoice_user->amount,
                        ],
                    ],
                ]
            ],
        ];
        
        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();
        
        $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Invoice');

        return $fileUrl;
    }
}
