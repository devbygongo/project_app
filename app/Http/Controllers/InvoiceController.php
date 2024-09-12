<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;    
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;

class InvoiceController extends Controller
{
    //
    public function generateInvoice($orderId)
    {
        $get_user = Auth::id();
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id', $get_user)
                    ->get();
        
        $order = OrderModel::select('order_id', 'amount', 'type', 'order_date')
                            ->where('id', $orderId)
                            ->get();

        $order_items = OrderItemsModel::select('product_code', 'product_name', 'rate', 'quantity', 'total')
                                    ->where('order_id', $orderId)
                                    ->get();
        if (!isset($user[0]) || !isset($order[0]) || !isset($order_items[0])) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }
        else {

            // Sanitize the order ID by removing slashes, backslashes, carriage return, and newline characters
            // $sanitizedOrderId = preg_replace('/[\/\\\r\n]+/', '-', $order[0]->order_id); // Replace problematic characters with dashes
            $sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', trim($order[0]->order_id));

            // Trim any leading or trailing dashes caused by the replacement
            $sanitizedOrderId = trim($sanitizedOrderId, '-');

            // $orderId = $order[0]->order_id;

            $qrCode = QrCode::format('svg')
                            ->size(100)
                            ->generate("Order ID: {$order[0]->order_id}, Order Date: {$order[0]->order_date}, Total: {$order[0]->amount}, Order Type: {$order[0]->type}");

            // Remove the XML declaration from the QR code SVG
            $qrCode = preg_replace('/<\?xml.+\?>/', '', $qrCode);
            
            // Prepare data to pass into the view
            $data = [
                'user_name' => $user[0]->name,
                'user_mobile' => $user[0]->mobile,
                'user_email' => $user[0]->email,
                'user_address1' => $user[0]->address_line_1,
                'user_address2' => $user[0]->address_line_2,
                'user_gstin' => $user[0]->gstin,
                'order_id' => $order[0]->order_id,
                'amount' => $order[0]->amount,
                'type' => $order[0]->type,
                'order_date' => $order[0]->order_date,
                'qrCode' => $qrCode,
                'product_name' => $order_items[0]->product_name,
                'product_code' => $order_items[0]->product_code,
                'product_rate' => $order_items[0]->rate,
                'product_quantity' => $order_items[0]->quantity,
                'product_total' => $order_items[0]->total,
            ];

            // Render the invoice view to HTML
            $html = view('invoice_template', $data)->render();

            // Create new mPDF instance
            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);

            // Define the directory path and file path
            $publicPath = 'uploads/invoices/';
            $fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
            $filePath = storage_path('app/public/' . $publicPath . $fileName);

            // Ensure the directory exists, if not, create it
            $directoryPath = storage_path('app/public/' . $publicPath);
            if (!File::isDirectory($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true, true); // Create the directory with recursive creation
            }

            // Save the file on the server
            $mpdf->Output($filePath, 'F'); // 'F' saves the file on the server

            // Create instance of your WhatsApp utility
            $whatsAppUtility = new sendWhatsAppUtility();
            $message = 'Here is your PDF document!';
            $fileUrl = asset('storage/' . $publicPath . $fileName);

            $update_order = OrderModel::where('id', $orderId)
            ->update([
                'order_invoice' => $fileUrl,
            ]);

            if ($update_order) {
                // Assuming sendWhatsApp method accepts a file URL for media
                $response = $whatsAppUtility->sendWhatsAppInvoice($user[0]->mobile, $message, $fileUrl);

                // Output the generated PDF in the browser
                return $mpdf->Output('invoice.pdf', 'I'); // 'I' sends it to the browser
            }
            else
            {
                return response()->json([
                    'message' => 'Error, failed to generate invoice!',
                ], 422);
            }
        }
    }
}
