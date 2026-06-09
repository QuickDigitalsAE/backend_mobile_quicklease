<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FirebaseNotificationControllers extends Controller
{
    public function sendUserNotification($FCMToken, $title, $body, $image = '', $data = [])
    {
        if (!$FCMToken) return;

        $accessToken = $this->getAccessTokenFromFirebase();

        $message = [
            "message" => [
                "token" => $FCMToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ],
                "android" => [
                    "notification" => [
                        "icon" => "ic_launcher",
                        "color" => "#7e55c3",
                        "image" => $image
                    ],
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "mutable-content" => 1,
                            "badge" => 1
                        ]
                    ],
                    "fcm_options" => [
                        "image" => $image
                    ]
                ]
            ]
        ];

        // Include custom data if provided
        if (!empty($data)) {
            $message['message']['data'] = $data;
        }

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post('https://fcm.googleapis.com/v1/projects/quicklease-app/messages:send', $message);

        return $response->json();
    }

    public function getAccessTokenFromFirebase()
    {
        try {
            // ✅ Get file path from storage
            $jsonPath = storage_path('app/firebase/quicklease-app-firebase-adminsdk-fbsvc-0f5242cb18.json');

            // ✅ Check if file exists
            if (!file_exists($jsonPath)) {
                throw new \Exception('Firebase JSON file not found at: ' . $jsonPath);
            }

            // ✅ Read JSON file
            $jsonContent = file_get_contents($jsonPath);
            $jsonKey = json_decode($jsonContent);

            // ✅ Validate JSON
            if (!$jsonKey || empty($jsonKey->client_email) || empty($jsonKey->private_key)) {
                throw new \Exception('Invalid Firebase JSON structure');
            }

            // ✅ Create JWT Header
            $header = json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT'
            ]);

            // ✅ Create JWT Claims
            $time = time();
            $claims = json_encode([
                'iss' => $jsonKey->client_email,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $time,
                'exp' => $time + 3600
            ]);

            // ✅ Encode
            $base64UrlHeader = $this->base64UrlEncode($header);
            $base64UrlPayload = $this->base64UrlEncode($claims);
            $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

            // ✅ Sign JWT
            openssl_sign($signatureInput, $signature, $jsonKey->private_key, 'sha256WithRSAEncryption');

            // ✅ Final JWT
            $jwt = $signatureInput . "." . $this->base64UrlEncode($signature);

            // ✅ Get Access Token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            $responseData = $response->json();

            if (!isset($responseData['access_token'])) {
                throw new \Exception('Unable to fetch access token');
            }

            return $responseData['access_token'];

        } catch (\Exception $e) {

            // ✅ Debug log (important)
            \Log::error('Firebase Token Error: ' . $e->getMessage());

            return null;
        }
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
