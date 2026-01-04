<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClearbitService
{
    private $apiKey;
    private $baseUrl = 'https://person.clearbit.com/v2/';

    public function __construct()
    {
        $this->apiKey = config('services.clearbit.api_key');
    }

    public function enrichPerson($email)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->timeout(30)
                ->get($this->baseUrl . 'combined/find', [
                    'email' => $email
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 404) {
                Log::info("Clearbit: No data found for {$email}");
                return null;
            }

            Log::error("Clearbit API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Clearbit API exception: " . $e->getMessage());
            return null;
        }
    }

    public function parseEnrichmentData($data)
    {
        if (!$data) return null;

        $person = $data['person'] ?? [];
        $company = $data['company'] ?? [];

        return [
            'first_name' => $person['name']['givenName'] ?? null,
            'last_name' => $person['name']['familyName'] ?? null,
            'job_title' => $person['employment']['title'] ?? null,
            'job_seniority' => $person['employment']['seniority'] ?? null,
            'company' => $company['name'] ?? null,
            'company_size' => $this->getCompanySizeRange($company['metrics']['employees'] ?? 0),
            'company_industry' => $company['category']['industry'] ?? null,
            'company_revenue' => $this->getRevenueRange($company['metrics']['estimatedAnnualRevenue'] ?? 0),
            'company_location' => $company['geo']['city'] ?? null,
            'company_website' => $company['domain'] ?? null,
            'company_description' => $company['description'] ?? null,
            'linkedin_url' => isset($person['linkedin']['handle']) 
                ? 'https://linkedin.com/in/' . $person['linkedin']['handle'] 
                : null,
            'twitter_url' => isset($person['twitter']['handle'])
                ? 'https://twitter.com/' . $person['twitter']['handle']
                : null,
        ];
    }

    private function getCompanySizeRange($employees)
    {
        if ($employees <= 10) return '1-10';
        if ($employees <= 50) return '11-50';
        if ($employees <= 200) return '51-200';
        return '200+';
    }

    private function getRevenueRange($revenue)
    {
        if ($revenue < 1000000) return '<$1M';
        if ($revenue < 10000000) return '$1M-$10M';
        return '$10M+';
    }
}