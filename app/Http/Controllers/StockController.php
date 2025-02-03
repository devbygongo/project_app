<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Mpdf\Mpdf;
use App\Models\StockOrderItemsModel;
use App\Models\ProductModel;
use App\Models\GodownModel;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class StockController extends Controller
{
    public function generateStockReport()
    {
        // Fetch stock data from the `t_stock_order_items` table
        $stockData = StockOrderItemsModel::with('stock_product', 'godown')->get();

        // Calculate current stock for each product
        $stockSummary = [];
        foreach ($stockData as $item) {
            $productCode = $item->product_code;
            
            if (!isset($stockSummary[$productCode])) {
                $stockSummary[$productCode] = [
                    'product_code' => $item->product_code,
                    'product_name' => $item->product_name,
                    'godown' => $item->godown->name ?? 'N/A',
                    'quantity' => 0,
                ];
            }

            // Add or subtract stock based on type (IN or OUT)
            if ($item->type === 'IN') {
                $stockSummary[$productCode]['quantity'] += $item->quantity;
            } elseif ($item->type === 'OUT') {
                $stockSummary[$productCode]['quantity'] -= $item->quantity;
            }
        }

        // Generate PDF
        $mpdf = new Mpdf();
        $mpdf->SetTitle("Stock Report");

        // Define the HTML content
        $html = '<h2 style="text-align:center;">Stock Report</h2>';
        $html .= '<p>Date: ' . Carbon::now()->format('d-m-Y H:i') . '</p>';
        $html .= '<table border="1" width="100%" cellpadding="5" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Godown</th>
                            <th>Stock Quantity</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $index = 1;
        foreach ($stockSummary as $stock) {
            $html .= "<tr>
                        <td>{$index}</td>
                        <td>{$stock['product_code']}</td>
                        <td>{$stock['product_name']}</td>
                        <td>{$stock['godown']}</td>
                        <td>{$stock['quantity']}</td>
                    </tr>";
            $index++;
        }

        $html .= '</tbody></table>';

        // Write HTML to PDF
        $mpdf->WriteHTML($html);

        // Define file path
        $filePath = storage_path('app/public/reports/');
        $fileName = 'Stock_Report_' . Carbon::now()->format('YmdHis') . '.pdf';

        // Ensure directory exists
        if (!File::exists($filePath)) {
            File::makeDirectory($filePath, 0755, true);
        }

        // Save PDF file
        $mpdf->Output($filePath . $fileName, 'F');

        // Return download link
        // return response()->json([
        //     asset('storage/reports/' . $fileName),
        // ]);

        return response()->file(storage_path('app/reports/' . $fileName));

    }
}

?>