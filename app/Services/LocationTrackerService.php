<?php

namespace App\Services;


use App\Models\UserActivityLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationTrackerService
{
    public static function track(
        string $eventType,
        string $actorType,
        int $actorId,
        Request $request,
        ?int $orderId = null
    ) {
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $locationData = [
            'country' => null,
            'state' => null,
            'city' => null,
            'postal_code' => null,
            'address' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        try {
            // Skip localhost IPs during local testing
            if ($ip && $ip !== "") {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

                if ($response->successful()) {
                    $geo = $response->json();

                    $locationData = [
                        'country' => $geo['country'] ?? null,
                        'state' => $geo['regionName'] ?? null,
                        'city' => $geo['city'] ?? null,
                        'postal_code' => $geo['zip'] ?? null,
                        'address' => null,
                        'latitude' => $geo['lat'] ?? null,
                        'longitude' => $geo['lon'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Optional: log error if needed
            // \Log::error('Location fetch failed: ' . $e->getMessage());
        }

        return UserActivityLocation::create([
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'order_id' => $orderId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'country' => $locationData['country'],
            'state' => $locationData['state'],
            'city' => $locationData['city'],
            'postal_code' => $locationData['postal_code'],
            'address' => $locationData['address'],
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'event_at' => now(),
        ]);
    }
}