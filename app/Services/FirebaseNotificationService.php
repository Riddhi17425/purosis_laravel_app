<?php

namespace App\Services;

use Google\Client as GoogleClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private string $projectId;
    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId       = config('services.firebase.project_id');
        $this->credentialsPath = config('services.firebase.credentials_path');
    }

    /**
     * Send a push notification via FCM HTTP v1 API.
     *
     * @param  string  $fcmToken  The device FCM registration token
     * @param  string  $title     Notification title
     * @param  string  $body      Notification body
     * @param  array   $data      Optional key-value data payload (all values must be strings)
     * @return bool
     */
    public function sendNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('Firebase: Could not retrieve OAuth2 access token.');
                return false;
            }

            $payload = [
                'message' => [
                    'token'        => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                ],
            ];

            if (!empty($data)) {
                // FCM data payload values must all be strings
                $payload['message']['data'] = array_map('strval', $data);
            }

            $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $httpClient = new HttpClient();
            $response = $httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            Log::error('Firebase notification failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getAccessToken(): ?string
    {
        $googleClient = new GoogleClient();
        $googleClient->setAuthConfig($this->credentialsPath);
        $googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $googleClient->fetchAccessTokenWithAssertion();

        return $token['access_token'] ?? null;
    }
}
