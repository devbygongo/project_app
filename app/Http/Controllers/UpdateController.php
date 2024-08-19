<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\CartModel;

use App\utils\WhatsAppService;

class UpdateController extends Controller
{
    //

    // public function __construct(whatsapputility $whatsapputility)
    // {
    //     $this->whatsapputility = $whatsapputility;
    // }

    public function generate_otp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'size:13'],
        ]);

        $mobile = $request->input('mobile');

        $get_user = User::select('id')
            ->where('mobile', $mobile)
            ->first();
            
        if (!$get_user == null) {

            $six_digit_otp_number = random_int(100000, 999999);

            $expiresAt = now()->addMinutes(10);

            $store_otp = User::where('mobile', $mobile)
                ->update([
                    'otp' => $six_digit_otp_number,
                    'expires_at' => $expiresAt,
                ]);
            
            if ($store_otp) {

                $templateParams = [
                    'name' => 'your_template_name', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en_US'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp_number,
                                ],
                            ],
                        ],
                    ],
                ];

                // Send OTP via WhatsApp
                $response = $this->whatsAppService->sendOtp($mobile, $templateParams);

                dd($response);

                return response()->json([
                    'message' => 'Otp store successfully!',
                    'data' => $store_otp
                ], 200);
            }

        else {
                return response()->json([
                'message' => 'Fail to store otp successfully!',
                'data' => $store_otp
                ], 501);
            }
        }

        else {
            return response()->json([
                'message' => 'User has not registered!',
            ], 404);
        }
    }

    public function cart($id)
    {
        $request->validate([
            'user_id' => 'required',
            'products_id' => 'required',
            // 'rate' => 'required',
            'quantity' => 'required',
            // 'amount' => 'required',
            'type' => 'required',
        ]);

            $update_cart = CartModel::where('id', $id)
            ->update([
                'products_id' => $request->input('products_id'),
                'quantity' => $request->input('quantity'),
                'type' => $request->input('type'),
            ]);

        if (isset($update_cart)) {
            return response()->json([
                'message' => 'Cart updated successfully!',
                'data' => $update_cart
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed to update cart successfully!'
            ], 400);
        }    
    }

    public function verify_user($get_id)
    {
        $update_verify = User::where('id', $get_id)
            ->update([
                'verified' => '1',
            ]);

            if ($update_verify == 1) {
                return response()->json([
                    'message' => 'User verified successfully!',
                    'data' => $update_verify
                ], 200);
            }
    
            else {
                return response()->json([
                    'message' => 'Failed to verify the user'
                ], 400);
            }    
    }
}