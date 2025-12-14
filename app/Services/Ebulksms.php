<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class Ebulksms
{

    protected $username;
    protected $apikey;
    protected $sendername;
    protected $url;

    public function __construct()
    {
        $this->username = config('sms.ebulksms.user_name');
        $this->apikey = config('sms.ebulksms.api_key');
        $this->sendername = config('sms.ebulksms.sender_name');
        $this->url = config('sms.ebulksms.api_url');
    }

    public function useJSON(string $messagetext, array $recipients)
    {
        $gsm = array();
        $country_code = '234';
        foreach ($recipients as $recipient) {
            $mobilenumber = trim($recipient);
            if (substr($mobilenumber, 0, 1) == '0') {
                $mobilenumber = $country_code . substr($mobilenumber, 1);
            } elseif (substr($mobilenumber, 0, 1) == '+') {
                $mobilenumber = substr($mobilenumber, 1);
            }
            $generated_id = uniqid('int_', false);
            $generated_id = substr($generated_id, 0, 30);
            $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
        }

        $message = array(
            'sender' => $this->sendername,
            'messagetext' => $messagetext,
            'flash' => "{0}",
        );

        $request = array('SMS' => array(
            'auth' => array(
                'username' => $this->username,
                'apikey' => $this->apikey
            ),
            'message' => $message,
            'recipients' => $gsm,
            'dndsender' => 0
        ));

        $json_data = json_encode($request);

            $response = $this->doPostRequest($this->url, $json_data, array('Content-Type: application/json'));

            Log::info('Ebulksms response', [
                'response' => $response
            ]);

            $result = json_decode($response);


            return $result->response;
       
    }


    //Function to connect to SMS sending server using HTTP POST
    private function doPostRequest($url, $arr_params, $headers = array('Content-Type: application/x-www-form-urlencoded'))
    {
        $response = array('code' => '', 'body' => '');
        $final_url_data = $arr_params;
        if (is_array($arr_params)) {
            $final_url_data = http_build_query($arr_params, '', '&');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $final_url_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        try {
            $response['body'] = curl_exec($ch);
            $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response['code'] != '200') {
                throw new Exception("Problem reading data from $url");
            }
            curl_close($ch);
        } catch (Exception $e) {
            echo 'cURL error: ' . $e->getMessage();
        }

        Log::info('res:', [$response]);
        return $response['body'];
    }
}
