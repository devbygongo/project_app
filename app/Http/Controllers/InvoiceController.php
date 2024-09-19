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

class InvoiceController extends Controller
{
    //
    public function generateorderInvoice($orderId)
    {
        $get_user = Auth::id();
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id', $get_user)
                    ->first();
        
        $order = OrderModel::select('order_id', 'amount', 'type', 'order_date')
                            ->where('id', $orderId)
                            ->first();

        $order_items = OrderItemsModel::with('product:product_code,sku')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total')
                                    ->where('order_id', $orderId)
                                    ->get();

        if (!$user || !$order || $order_items->isEmpty()) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }

        $sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', trim($order->order_id));
        $sanitizedOrderId = trim($sanitizedOrderId, '-');

        $data = [
            'user' => $user,
            'order' => $order,
            'order_items' => $order_items,
        ];

        $html = view('order_invoice_template', $data)->render();

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);

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
        
        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();
        
        $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Order Invoice');

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

        $response = $whatsAppUtility->sendWhatsApp('+919966633307', $templateParams, '', 'Admin Order Invoice');
        

        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
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
        
        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();
        
        $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Order Invoice');

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

        $response = $whatsAppUtility->sendWhatsApp('+919966633307', $templateParams, '', 'Admin Order Invoice');
        

        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
    }
}
