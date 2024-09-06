<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
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
        $user = User::select('name', 'mobile', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id',$get_user)
                    ->get();
        
        $order = OrderModel::select('order_id', 'amount', 'type', 'order_date')
                            ->where('id', $orderId)
                            ->get();

        // Replace invalid characters in $orderId (like slashes) to ensure it can be used as a filename
        $sanitizedOrderId = str_replace(['/', '\\'], '-', $order[0]->order_id); // Replace slashes with dashes
        $orderId = $order[0]->order_id;

        $qrCode = QrCode::format('svg')
                        ->size(100)
                        ->generate("Order ID: {$order[0]->order_id}, Order Date: {$order[0]->order_date}, Total: {$order[0]->amount}, Order Type: {$order[0]->type}");

        // Use preg_replace to remove the XML declaration
        $qrCode = preg_replace('/<\?xml.+\?>/', '', $qrCode);
        
        // Prepare data to pass into the view
        $data = [
            'user_name' => $user[0]->name,
            'user_mobile' => $user[0]->mobile,
            'user_address1' => $user[0]->address_line_1,
            'user_address2' => $user[0]->address_line_2,
            'user_gstin' => $user[0]->gstin,
            'order_id' => $order[0]->order_id,
            'amount' => $order[0]->amount,
            'type' => $order[0]->type,
            'order_data' => $order[0]->order_date,
            'qrCode' => $qrCode,
        ];

        // Render the invoice view to HTML
        $html = view('invoice_template', $data)->render();

        // Create new mPDF instance
        $mpdf = new Mpdf();

        // Write the HTML into the PDF
        $mpdf->WriteHTML($html);

    //    // Define the directory path and file path
    //     $directoryPath = storage_path('app/public/uploads/invoices/'); // Define your nested directory structure
    //     $filePath = $directoryPath . 'invoice_' . $sanitizedOrderId . '.pdf';

    
        $publicPath = 'uploads/invoices/';
        $fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        // Ensure the directory exists, if not, create it
        // if (!File::isDirectory($directoryPath)) {
        //     File::makeDirectory($directoryPath, 0755, true, true); // Create the directory with recursive creation
        // }
        if (!File::isDirectory(storage_path('app/public/' . $publicPath))) {
            File::makeDirectory(storage_path('app/public/' . $publicPath), 0755, true, true);
        }

        // Save the file on the server
        $mpdf->Output($filePath, 'F'); // 'F' saves the file on the server

        // Create instance of your WhatsApp utility
        $whatsAppUtility = new sendWhatsAppUtility();

        $message = 'Here is your PDF document!';

        $fileUrl = asset('storage/' . $publicPath . $fileName);
        // Assuming sendWhatsApp method accepts a file URL for media
        $response = $whatsAppUtility->sendWhatsAppInvoice($user[0]->mobile, $message, $fileUrl);

        // Output the generated PDF in the browser
        return $mpdf->Output('invoice.pdf', 'I'); // 'I' sends it to the browser
        
    }
}
