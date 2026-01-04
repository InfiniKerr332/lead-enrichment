<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HunterService
{
    private $apiKey;
    private $baseUrl = 'https://api.hunter.io/v2/';

    public function __construct()
    {
        $this->apiKey = config('services.hunter.api_key');
    }

    public function verifyEmail($email)
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . 'email-verifier', [
                'email' => $email,
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                
                return [
                    'valid' => ($data['status'] ?? '') === 'valid',
                    'score' => $data['score'] ?? 0,
                    'result' => $data['result'] ?? 'unknown'
                ];
            }

            Log::error("Hunter API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Hunter API exception: " . $e->getMessage());
            return null;
        }
    }

    public function findEmail($domain, $firstName, $lastName)
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . 'email-finder', [
                'domain' => $domain,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json()['data']['email'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Hunter API exception: " . $e->getMessage());
            return null;
        }
    }
}