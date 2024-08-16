namespace App\Utility;

class sendWhatsAppUtility 
{
    public static function sendWhatsApp($customer, $params, $media, $campaignName) 
    {
        if (env('WHATSAPP_SERVICE_ON')) 
        {

            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $customer->phone;
            $content['type'] = 'template';
            $content['biz_opaque_callback_data'] = 'testing_mazing';
            $content['template'] = $params;

            $token = env('WHATSAPP_API_TOKEN');

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v18.0/147572895113819/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($content),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

        }
    return true;
    }
}