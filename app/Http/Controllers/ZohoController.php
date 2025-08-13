<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderModel;

use App\Models\OrderItemsModel;

class ZohoController extends Controller
{
    // Function to get the access token using the refresh token
    public function getAccessToken()
    {
        $response = Http::asForm()->post('https://accounts.zoho.in/oauth/v2/token', [
            'client_id' => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_CLIENT_SECRET'),
            'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
            'grant_type' => 'refresh_token',
            'scope' => 'ZohoBooks.estimates.ALL',
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        return response()->json(['error' => 'Failed to fetch access token', 'details' => $response->json()], 400);
    }

    // Function to create an estimate in Zoho Books
    public function createEstimate()
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $organizationId = '60012918151';  // Use your correct organization ID

        $estimateData = [
            "customer_id" => 786484000000198301,  // Replace with actual customer ID
            "date" => now()->format('Y-m-d'),
            "line_items" => [
                [
                    "name" => "Custom Product A",  // Custom item name
                    "description" => "Description for Custom Product A",  // Optional description
                    "quantity" => 2,
                    "rate" => 100.00,
                    "amount" => 200.00,
                ],
                [
                    "name" => "Custom Product B",  // Custom item name
                    "description" => "Description for Custom Product B",  // Optional description
                    "quantity" => 3,
                    "rate" => 50.00,
                    "amount" => 150.00,
                ],
            ],
            "total" => 350.00,  // Total amount
            "status" => "draft",  // Status can be 'draft' or 'sent'
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->post(env('ZOHO_API_BASE_URL') . '/books/v3/estimates', $estimateData);

        if ($response->successful()) {
            return response()->json(['message' => 'Estimate created successfully', 'data' => $response->json()]);
        }

        return response()->json(['error' => 'Failed to create estimate', 'details' => $response->json()], 400);
    }

    public function zoho_quote(Request $request)
    {
        $get_user = Auth::user();  // Get the authenticated user

        // Validate the incoming request to ensure order_id is provided
        $request->validate([
            'order_id' => 'required|string',
        ]);

        // Fetch the order using the order_id passed in the request
        $order = OrderModel::with('order_items.product')  // Eager load order items and product details
            ->where('order_id', $request->input('order_id'))
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        // Calculate the tax-exclusive total and tax amount if the order total is inclusive of tax
        // Assuming tax is 18% (adjust the tax rate as needed)
        $taxRate = 0.18;  // Example: 18% tax

        $taxExclusiveAmount = $order->amount / (1 + $taxRate);  // Exclude tax
        $taxAmount = $order->amount - $taxExclusiveAmount;  // Calculate the tax amount

        // Prepare the line items for the Zoho quote
        $lineItems = [];

        foreach ($order->order_items as $item) {
            $lineItems[] = [
                "name" => $item->product_name.' '.$item->product_code,  // Using product name
                "description" => $item->remarks ?? "No description",  // Optional description
                "quantity" => $item->quantity,
                "rate" => $item->rate,
                "amount" => $item->total,  // The total of each item (including tax)
            ];
        }

        // Now create the estimate (quote) data for Zoho Books
        $estimateData = [
            "customer_id" => 786484000000198301,  // Assuming the user_id is the customer_id in Zoho Books
            "date" => now()->format('Y-m-d'),
            "line_items" => $lineItems,
            "total" => $taxExclusiveAmount,  // Total excluding tax
            "status" => "draft",  // Status can be 'draft' or 'sent'
            "tax" => [
                "name" => "GST",  // Replace with your actual tax name (e.g., GST, VAT, etc.)
                "percentage" => $taxRate * 100,  // Tax rate (as a percentage)
                "amount" => $taxAmount,  // Tax amount to be applied
            ],
        ];

        // Get access token and the organization ID
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $organizationId = '60012918151';  // Replace with your actual organization ID

        // Send the request to Zoho Books to create the estimate (quote)
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->post(env('ZOHO_API_BASE_URL') . '/books/v3/estimates', $estimateData);

        if ($response->successful()) {
            return response()->json([
                'message' => "Order : " . $order->order_id . " has been successfully pushed as a quote to Zoho.",
                'data' => $response->json()
            ], 200);
        }

        return response()->json(['error' => 'Failed to create estimate', 'details' => $response->json()], 400);
    }



}