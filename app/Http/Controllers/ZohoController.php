<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        return response()->json(['error' => 'Failed to fetch access token'], 400);
    }

    // Function to create an estimate in Zoho
    public function createEstimate()
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $estimateData = [
            "data" => [
                [
                    "customer_name" => "John Doe",  // Replace with customer name
                    "date" => now()->format('Y-m-d'),  // Current date
                    "line_items" => [
                        [
                            "item_name" => "Product A",
                            "quantity" => 2,
                            "rate" => 100.00,
                            "total" => 200.00,
                        ],
                        [
                            "item_name" => "Product B",
                            "quantity" => 3,
                            "rate" => 50.00,
                            "total" => 150.00,
                        ],
                    ],
                    "total" => 350.00,
                    "status" => "draft",
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->post(env('ZOHO_API_BASE_URL') . '/crm/v2/Deals', $estimateData);

        if ($response->successful()) {
            return response()->json(['message' => 'Estimate created successfully', 'data' => $response->json()]);
        }

        return response()->json(['error' => 'Failed to create estimate', 'details' => $response->json()], 400);
    }
}
