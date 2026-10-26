<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public static function sendSms($receiver, $message, $count = null)
    {
        /* if ($count % 2) {
            // Odd count: Use sendReve
            $success = self::sendReve($receiver, $message);
        } else {
            // Even count: Use sendElitbuz
            $success = self::sendElitbuz($receiver, $message);
        } */

        $success = self::sendReve($receiver, $message);

        return $success;
    }

    public static function sendReve($receiver, $message)
    {
        if (env('APP_MODE') != 'production') {
            return true;
        }

        $sms_url = env('SMS_URL_REVE');
        $api_key = env('SMS_API_KEY_REVE');
        $secret_key = env('SMS_SECRET_KEY_REVE');
        $sender_id = env('SMS_SENDER_ID_REVE');

        if ($sms_url == null || $api_key == null || $secret_key == null || $sender_id == null) {
            return false;
        }
        $message = urlencode($message);
        $url = $sms_url . '?apikey=' . $api_key . '&secretkey=' . $secret_key . '&callerID=' . $sender_id . '&toUser=' . $receiver . '&messageContent=' . $message;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($url);

            // $res = $response->json();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function sendElitbuz($receiver, $mes)
    {
        // if (env('APP_MODE') != 'production') {
        //     return true;
        // }

        $msisdn = $receiver;
        $message = $mes;

        $sms_url = env('SMS_URL_ELITBUZ');
        $api_key = env('SMS_API_KEY_ELITBUZ');
        $sender_id = env('SMS_SENDER_ID_ELITBUZ');

        $message = urlencode($message);
        $url = $sms_url . '?api_key=' . $api_key . '&type=text&contacts=' . $receiver . '&senderid=' . $sender_id . '&msg=' . $message;

        try {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_exec($ch);
            curl_close($ch);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
