<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderModel;
use App\Models\User;
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
        
        $request->validate([
            'order_id' => 'required',
        ]);

        $order = OrderModel::with('order_items.product')
            ->where('id', $request->input('order_id'))
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        $user = User::find($order->user_id);

        // --- CONFIG ---
        $organizationId  = '60012918151';
        $orgStateName    = env('ORG_STATE_NAME', 'Telangana');

        $cgstSgstTaxIdMap = [ 5=>'786484000000013205', 12=>'786484000000013213', 18=>'786484000000013221', 28=>'786484000000013229' ];
        $igstTaxIdMap     = [ 5=>'786484000000013199', 12=>'786484000000013207', 18=>'786484000000013215', 28=>'786484000000013223' ];

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $customer_id = $user->zoho_customer_id ?: '786484000000198301';

        // === DUPLICATE CHECK: block if an estimate already exists with same reference_number ===
        // Try using search_text first (broad), then fall back to direct filter.
        $dupExists = false;

        // 1) Broad search via search_text (then exact-match locally)
        $searchResp = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->get(env('ZOHO_API_BASE_URL') . '/books/v3/estimates', [
                'search_text'  => (string) $order->order_id,
                'customer_id'  => $customer_id,   // optional: narrows search
                'page'         => 1,
                'per_page'     => 200,
            ]);

        if ($searchResp->successful()) {
            $hits = $searchResp->json('estimates') ?? [];
            foreach ($hits as $hit) {
                if (isset($hit['reference_number']) && $hit['reference_number'] === $order->order_id) {
                    $dupExists = true;
                    break;
                }
            }
        }

        // 2) If still not found, try a direct equality filter (some orgs support this param)
        if (!$dupExists) {
            $eqResp = Http::withToken($accessToken)
                ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
                ->get(env('ZOHO_API_BASE_URL') . '/books/v3/estimates', [
                    'reference_number' => (string) $order->order_id,
                    'customer_id'      => $customer_id,
                    'page'             => 1,
                    'per_page'         => 50,
                ]);

            if ($eqResp->successful()) {
                $hits = $eqResp->json('estimates') ?? [];
                foreach ($hits as $hit) {
                    if (isset($hit['reference_number']) && $hit['reference_number'] === $order->order_id) {
                        $dupExists = true;
                        break;
                    }
                }
            }
        }

        if ($dupExists) {
            return response()->json([
                'error'   => 'Duplicate estimate',
                'message' => 'This order has already been punched to Zoho. Kindly delete the estimate on Zoho to re-punch this order.',
            ], 400);
        }
        // === /DUPLICATE CHECK ===

        // --- intra vs inter from user's saved state ---
        $custState = strtolower(trim($user->state ?? ''));
        $orgState  = strtolower($orgStateName);
        $isInter   = ($custState && $custState !== $orgState);
        $taxIdMap  = $isInter ? $igstTaxIdMap : $cgstSgstTaxIdMap;

        // --- Build line items (exclusive) ---
        $lineItems = [];
        $supported = [5, 12, 18, 28];

        foreach ($order->order_items as $item) {
            $product = $item->product;
            $rawPct  = $product->gst ?? $product->tax ?? null;

            $pct = (int) round((float) $rawPct);
            if (!in_array($pct, $supported, true)) { $pct = 18; }

            $pctRate = $pct / 100.0;
            $exclusiveRate  = (float) $item->rate  / (1 + $pctRate);
            $exclusiveTotal = (float) $item->total / (1 + $pctRate);

            $lineItems[] = [
                "name"         => trim(($item->product_name ?? '') . ' - ' . ($item->product_code ?? '')),
                "description"  => $item->remarks ?? "",
                "quantity"     => (float) $item->quantity,
                "rate"         => round($exclusiveRate, 2),
                "amount"       => round($exclusiveTotal, 2),
                "tax_id"       => $taxIdMap[$pct] ?? null,
                "hsn_or_sac"   => optional($product)->hsn,
                "product_type" => "goods",
            ];
        }

        $estimateData = [
            "customer_id"      => $customer_id,
            "date"             => now()->format('Y-m-d'),
            "reference_number" => $order->order_id,
            "line_items"       => $lineItems,
            "status"           => "draft",
            // Optionally set place_of_supply if you maintain a code map; otherwise omit
            // "place_of_supply"  => $isInter ? 'AD' : 'TS',
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->post(env('ZOHO_API_BASE_URL') . '/books/v3/estimates', $estimateData);

        if ($response->successful()) {
            return response()->json([
                'message' => "Order : {$order->order_id} has been successfully pushed as a quote to Zoho.",
                'data'    => $response->json()
            ], 200);
        }

        return response()->json([
            'error'   => 'Failed to create estimate',
            'details' => $response->json()
        ], 400);
    }

    public function zoho_sales(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
        ]);

        $order = OrderModel::with('order_items.product')
            ->where('id', $request->input('order_id'))
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found!'], 404);
        }

        $user = User::find($order->user_id);

        // --- CONFIG ---
        $organizationId = '60012918151';
        $orgStateName   = env('ORG_STATE_NAME', 'Telangana');

        $cgstSgstTaxIdMap = [
            5  => '786484000000013205',
            12 => '786484000000013213',
            18 => '786484000000013221',
            28 => '786484000000013229',
        ];
        $igstTaxIdMap = [
            5  => '786484000000013199',
            12 => '786484000000013207',
            18 => '786484000000013215',
            28 => '786484000000013223',
        ];

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        // ✅ Require customer_id (no fallback)
        $customer_id = $user->zoho_customer_id ?? null;
        if (!$customer_id) {
            return response()->json([
                'error'   => 'Missing Zoho customer',
                'message' => "This user does not have a Zoho customer_id mapped. Please create/link the customer in Zoho before punching the invoice."
            ], 422);
        }

        // === DUPLICATE CHECK ===
        $dupExists = false;
        $searchResp = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->get(env('ZOHO_API_BASE_URL') . '/books/v3/invoices', [
                'search_text' => (string) $order->order_id,
                'customer_id' => $customer_id,
                'page'        => 1,
                'per_page'    => 200,
            ]);
        if ($searchResp->successful()) {
            $hits = $searchResp->json('invoices') ?? [];
            foreach ($hits as $hit) {
                if (($hit['reference_number'] ?? null) === $order->order_id) {
                    $dupExists = true; break;
                }
            }
        }
        if ($dupExists) {
            return response()->json([
                'error'   => 'Duplicate invoice',
                'message' => 'This order has already been punched to Zoho as an invoice. Kindly delete the invoice on Zoho to re-punch this order.',
            ], 409);
        }
        // === /DUPLICATE CHECK ===

        // --- intra vs inter ---
        $custState = strtolower(trim($user->state ?? ''));
        $orgState  = strtolower($orgStateName);
        $isInter   = ($custState && $custState !== $orgState);
        $taxIdMap  = $isInter ? $igstTaxIdMap : $cgstSgstTaxIdMap;

        // --- Line items ---
        $supported = [5, 12, 18, 28];
        $lineItems = [];

        foreach ($order->order_items as $item) {
            $product = $item->product;
            $rawPct  = $product->gst ?? $product->tax ?? null;

            $pct = (int) round((float) $rawPct);
            if (!in_array($pct, $supported, true)) { $pct = 18; }

            $pctRate = $pct / 100.0;
            $exclusiveRate  = (float) $item->rate  / (1 + $pctRate);
            $exclusiveTotal = (float) $item->total / (1 + $pctRate);

            $lineItems[] = [
                "name"         => trim(($item->product_name ?? '') . ' - ' . ($item->product_code ?? '')),
                "description"  => $item->remarks ?? "",
                "quantity"     => (float) $item->quantity,
                "rate"         => round($exclusiveRate, 2),
                "amount"       => round($exclusiveTotal, 2),
                "tax_id"       => $taxIdMap[$pct] ?? null,
                "hsn_or_sac"   => optional($product)->hsn,
                "product_type" => "goods",
            ];
        }

        $invoiceData = [
            "customer_id"      => $customer_id,
            "date"             => now()->format('Y-m-d'),
            "reference_number" => $order->order_id,
            "line_items"       => $lineItems,
            "status"           => "draft",
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->post(env('ZOHO_API_BASE_URL') . '/books/v3/invoices', $invoiceData);

        if ($response->successful()) {
            return response()->json([
                'message' => "Order : {$order->order_id} has been successfully pushed as an invoice to Zoho.",
                'data'    => $response->json(),
            ], 200);
        }

        return response()->json([
            'error'   => 'Failed to create invoice',
            'details' => $response->json(),
        ], 400);
    }


    public function getEstimate(string $estimateId, Request $request)
    {
        // If you also want to allow ?estimateId=... as a fallback:
        $estimateId = $estimateId ?: $request->query('estimateId');

        if (!$estimateId) {
            return response()->json(['error' => 'estimateId is required'], 422);
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Unable to retrieve access token'], 400);
        }

        $organizationId = '60012918151'; // your org id

        $response = Http::withToken($accessToken)
            ->withHeaders(['X-com-zoho-books-organizationid' => $organizationId])
            ->get(env('ZOHO_API_BASE_URL') . '/books/v3/estimates/' . $estimateId);

        if ($response->successful()) {
            return response()->json([
                'message' => 'Estimate fetched successfully',
                'data' => $response->json(),
            ], 200);
        }

        return response()->json([
            'error'   => 'Failed to fetch estimate',
            'details' => $response->json(),
        ], 400);
    }


}