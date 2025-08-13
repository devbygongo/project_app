<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderModel;
use Illuminate\Support\Facades\Log;

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
        return response()->json([
            'message' => $message,
            'data' => 'Successfully pushed!'
        ], 200);
        $get_user = Auth::user();  // Get the authenticated user

        // Validate the incoming request to ensure order_id is provided
        $request->validate([
            'order_id' => 'required',
        ]);

        Log::info('Zoho Quote Request', ['user_id' => $get_user->id, 'order_id' => $request->input('id')]);

        // Fetch the order using the order_id passed in the request
        $order = OrderModel::with('order_items.product')  // Eager load order items and product details
            ->where('order_id', $request->input('id'))
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        // Tax rate for GST18 (18%)
        $taxRate = 0.18;  // Example: 18% tax (GST18)

        // Calculate the tax-exclusive total and tax amount if the order total is inclusive of tax
        $taxExclusiveAmount = $order->amount / (1 + $taxRate);  // Exclude tax
        $taxAmount = $order->amount - $taxExclusiveAmount;  // Calculate the tax amount

        // Tax ID for GST18 (replace with your actual tax_id for GST18)
        $taxId = '786484000000013221';  // GST18 tax ID

        // Prepare the line items for the Zoho quote
        $lineItems = [];

        foreach ($order->order_items as $item) {
            // Calculate tax-exclusive rate for each item
            $taxExclusiveRate = $item->rate / (1 + $taxRate);  // Exclude tax from rate
            $taxExclusiveAmountForItem = $item->total / (1 + $taxRate);  // Exclude tax from total

            $lineItems[] = [
                "name" => $item->product_name.' '.$item->product_code,  // Using product name
                "description" => $item->remarks ?? "No description",  // Optional description
                "quantity" => $item->quantity,
                "rate" => $taxExclusiveRate,  // Tax-exclusive rate
                "amount" => $taxExclusiveAmountForItem,  // Tax-exclusive amount
                "tax_id" => $taxId,  // Pass the correct tax_id for GST18
            ];
        }

        // Now create the estimate (quote) data for Zoho Books
        $estimateData = [
            "customer_id" => 786484000000198301,  // Assuming the user_id is the customer_id in Zoho Books
            "date" => now()->format('Y-m-d'),
            "line_items" => $lineItems,
            "total" => $taxExclusiveAmount,  // Total amount excluding tax
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


    public function getEstimate()
    {
        $estimateId = 786484000002755001;
        $accessToken = $this->getAccessToken();  // Get the access token

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $organizationId = '60012918151';  // Replace with your actual organization ID

        // Send the GET request to Zoho Books API to fetch the estimate details
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->get(env('ZOHO_API_BASE_URL') . '/books/v3/estimates/' . $estimateId);

        if ($response->successful()) {
            // Return the estimate data
            return response()->json([
                'message' => 'Estimate fetched successfully',
                'data' => $response->json()
            ], 200);
        }

        // Handle failure response
        return response()->json([
            'error' => 'Failed to fetch estimate',
            'details' => $response->json()
        ], 400);
    }








}