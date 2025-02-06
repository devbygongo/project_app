<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpdf\Mpdf;
use App\Models\StockOrderItemsModel;
use App\Models\GodownModel;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class StockController extends Controller
{
    public function generateStockReport()
    {
        // Fetch stock data from `t_stock_order_items`
        $stockData = StockOrderItemsModel::with('stock_product', 'godown')->get();

        // Organize stock by product and godown
        $stockSummary = [];
        foreach ($stockData as $item) {
            $productCode = $item->product_code;
            $godown = $item->godown->name ?? 'N/A';

            if (!isset($stockSummary[$productCode])) {
                $stockSummary[$productCode] = [
                    'product_code' => $item->product_code,
                    'product_name' => $item->product_name,
                    'godowns' => [],
                    'total_stock' => 0
                ];
            }

            // Initialize godown stock if not set
            if (!isset($stockSummary[$productCode]['godowns'][$godown])) {
                $stockSummary[$productCode]['godowns'][$godown] = 0;
            }

            // Update stock based on type (IN or OUT)
            if ($item->type === 'IN') {
                $stockSummary[$productCode]['godowns'][$godown] += $item->quantity;
                $stockSummary[$productCode]['total_stock'] += $item->quantity;
            } elseif ($item->type === 'OUT') {
                $stockSummary[$productCode]['godowns'][$godown] -= $item->quantity;
                $stockSummary[$productCode]['total_stock'] -= $item->quantity;
            }
        }

        // Fetch all Godown Names dynamically
        $allGodowns = GodownModel::pluck('name')->toArray();

        // Generate PDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape for better table display
            'margin_top' => 25, // Adjusted for header spacing
        ]);

        $mpdf->SetTitle("Stock Report");

        // HTML Header (Repeated on Each Page)
        $headerHtml = '<table border="1" width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part No</th>
                                <th>Product Name</th>';
        foreach ($allGodowns as $godown) {
            $headerHtml .= "<th>{$godown}</th>";
        }
        $headerHtml .= '<th>Total Stock</th>
                        </tr>
                    </thead>
                </table>';

        $mpdf->SetHTMLHeader($headerHtml);

        // HTML Content
        $html = '<div style="text-align:center;">
                    <h2>Stock Report</h2>
                    <p>Date: ' . Carbon::now()->format('d-m-Y H:i') . '</p>
                 </div>';

        // Start table
        $html .= '<table border="1" width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                    <thead>' . $headerHtml . '</thead>
                    <tbody>';

        // Populate table rows
        $index = 1;
        foreach ($stockSummary as $product) {
            $html .= "<tr>
                        <td>{$index}</td>
                        <td>{$product['product_code']}</td>
                        <td>{$product['product_name']}</td>";

            // Fill stock for each godown
            foreach ($allGodowns as $godown) {
                $stockInGodown = $product['godowns'][$godown] ?? 0;
                $html .= "<td>{$stockInGodown}</td>";
            }

            $html .= "<td>{$product['total_stock']}</td>
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
        return response()->json([
            'download_url' => asset('storage/reports/' . $fileName),
        ]);
    }
}
?>
