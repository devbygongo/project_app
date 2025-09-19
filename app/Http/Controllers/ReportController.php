<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderModel;
use App\Models\OrderItemsModel;
use App\Models\ProductModel;
use App\Models\User;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function pendingOrderReport(Request $request)
    {

        $sendWhatsApp = $request->input('send_whatsapp', true); // default true

        // Fetch pending orders
        $pendingOrders = OrderModel::select('order_id', 'order_date', 'type', 'user_id', 'amount')
            ->where('status', 'pending') // Assuming 'pending' is the status for pending orders
            ->get();

        if ($pendingOrders->isEmpty()) {
            return response()->json(['error' => 'No pending orders found!'], 404);
        }

        // Add client names to the orders
        $ordersWithClients = $pendingOrders->map(function ($order) {
            $user = User::select('name')->where('id', $order->user_id)->first();
            $order->client_name = $user ? $user->name : 'N/A';
            return $order;
        });

        // Create PDF
        $mpdf = new Mpdf();

        // Header
        $headerHtml = "
            <center><h1>Super Steel - Ace Care</h1></center>
            <center><h2>Pending orders as of " . Carbon::now()->format('d-m-Y') . "</h2></center>
        ";
        $mpdf->WriteHTML($headerHtml);

        // Table with orders
        $tableHtml = "<style>
            table {
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
            }
            th {
                background-color: #f2f2f2;
                color: #333;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            tr:nth-child(odd) {
                background-color: #ffffff;
            }
            .type-gst {
                background-color: #d4edda; /* Subtle green */
                color: #155724;
            }
            .type-other {
                background-color: #d1ecf1; /* Subtle blue */
                color: #0c5460;
            }
        </style>
        <table border='1' cellpadding='5' cellspacing='0' width='100%'>
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Client Name</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>";

        foreach ($ordersWithClients as $index => $order) {
            $tableHtml .= "
                <tr>
                    <td>" . ($index + 1) . "</td>
                    <td>" . $order->order_id . "</td>
                    <td>" . Carbon::parse($order->order_date)->format('d-m-Y') . "</td>
                    <td>" . $order->type . "</td>
                    <td>" . $order->client_name . "</td>
                    <td>" . number_format($order->amount, 2) . "</td>
                </tr>
            ";
        }

        $tableHtml .= "</tbody></table>";
        $mpdf->WriteHTML($tableHtml);

        // Save PDF
        $publicPath = 'uploads/reports/';
        $fileName = 'pending_orders_' . Carbon::now()->format('Ymd') . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        $mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        if ($sendWhatsApp) {
            // Send WhatsApp message
            $whatsAppUtility = new sendWhatsAppUtility();
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'pending_order_report', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' => $fileUrlWithTimestamp,
                                    'filename' => $fileName,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => "Pending Orders",
                            ],
                            [
                                'type' => 'text',
                                'text' => Carbon::now()->format('d-m-Y'),
                            ],
                        ],
                    ],
                ],
            ];

            $adminMobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();

            foreach ($adminMobileNumbers as $mobileNumber) {
                if($mobileNumber == '+918961043773' || true)
                {
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Pending Order Report');
                    if (isset($response['error'])) {
                        echo "Failed to send WhatsApp message to $mobileNumber!";
                    }
                }
            }
        }

        return response()->json(['success' => 'Pending order report generated and sent successfully!', 'file_url' => $fileUrl], 200);
    }

    public function topSellingProducts(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer|exists:t_category,id',
            'user_id'     => 'nullable|integer|exists:users,id',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'search'      => 'nullable|string'
        ]);

        $categoryId = $request->category_id;
        $userId     = $request->user_id;
        $dateFrom   = $request->date_from;
        $dateTo     = $request->date_to;
        $search     = $request->search;

        $query = OrderItemsModel::query()
            ->select(
                't_order_items.product_code',
                't_products.product_name as name',
                't_products.category',
                \DB::raw('SUM(t_order_items.quantity) as qty_sold'),
                \DB::raw('SUM(t_order_items.total) as total_amount')
            )
            ->join('t_orders', 't_order_items.order_id', '=', 't_orders.id')
            ->join('t_products', 't_order_items.product_code', '=', 't_products.product_code')
            ->where('t_orders.status', 'completed')
            ->groupBy('t_order_items.product_code', 't_products.product_name', 't_products.category')
            ->orderByDesc('qty_sold'); // top selling first

        // Filter by category
        if ($categoryId) {
            $query->where('t_products.category', function($q) use ($categoryId) {
                $q->select('name')->from('t_category')->where('id', $categoryId)->limit(1);
            });
        }

        // Filter by user
        if ($userId) {
            $query->where('t_orders.user_id', $userId);
        }

        // Filter by date range
        if ($dateFrom && $dateTo) {
            $query->whereBetween('t_orders.order_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('t_orders.order_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('t_orders.order_date', '<=', $dateTo);
        }

        // Search by product code or name
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('t_products.product_code', 'like', "%$search%")
                ->orWhere('t_products.product_name', 'like', "%$search%");
            });
        }

        $topSelling = $query->get();

        return response()->json([
            'success' => true,
            'data' => $topSelling
        ]);
    }
}
