<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    public function notify(array $tokens, string $title, string $body, $data = [])
    {
        if (empty($tokens)) {
            return ['status' => 'error', 'message' => 'No FCM tokens provided'];
        }

        try {
            // $options = HttpClientOptions::default();
            if (env('APP_ENV') != 'production') {
                return true;
                // $options = $options->withProxy('10.100.100.103:3128');
            }

            $firebase = (new Factory)
                ->withServiceAccount(config('firebase.credentials'));
            // ->withHttpClientOptions($options);

            $messaging = $firebase->createMessaging();

            $notification = Notification::create($title, $body);

            // Firebase limits to 450 tokens per request
            $chunks = array_chunk($tokens, 450);
            foreach ($chunks as $chunk) {
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData($data); // Include custom data

                $responses[] = $messaging->sendMulticast($message, $chunk);
            }

            // $message = CloudMessage::new()->withNotification($notification);
            // $response = $messaging->sendMulticast($message, $tokens);

            return true;

            /* foreach ($tokens as $token) {
                $message = CloudMessage::withTarget('token', $token)
                    // ->withNotification($notification)
                    ->withData(['title' => $title, 'body' => $body]);

                 $messaging->send($message);
            } */
            // return response()->json(['message' => 'Push notifications sent successfully']);
        } catch (\Exception $e) {
            return false;
            // return response()->json(['error' => 'Failed to send push notifications: ' . $e->getMessage()], 500);
        }
    }
}
