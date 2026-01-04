<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEnrichment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LeadEnrichmentService
{
    private $clearbit;
    private $hunter;

    public function __construct(ClearbitService $clearbit, HunterService $hunter)
    {
        $this->clearbit = $clearbit;
        $this->hunter = $hunter;
    }

    public function enrichLead(Lead $lead)
    {
        try {
            Log::info("Starting enrichment for lead", ['lead_id' => $lead->id, 'email' => $lead->email]);

            // Update status to enriching
            $lead->update(['status' => 'enriching']);

            // Step 1: Verify email
            $verification = $this->hunter->verifyEmail($lead->email);
            
            if (!$verification || !$verification['valid']) {
                Log::warning("Email verification failed", ['lead_id' => $lead->id]);
                $lead->update(['status' => 'invalid_email']);
                return false;
            }

            // Step 2: Enrich with Clearbit
            $enrichmentData = $this->clearbit->enrichPerson($lead->email);
            
            if (!$enrichmentData) {
                Log::warning("Clearbit enrichment returned no data", ['lead_id' => $lead->id]);
                $lead->update(['status' => 'enriched']);
                return false;
            }

            // Step 3: Parse and save enrichment data
            $parsed = $this->clearbit->parseEnrichmentData($enrichmentData);
            
            DB::transaction(function () use ($lead, $parsed, $enrichmentData) {
                // Create enrichment record
                LeadEnrichment::create([
                    'lead_id' => $lead->id,
                    'company_size' => $parsed['company_size'],
                    'company_industry' => $parsed['company_industry'],
                    'company_revenue' => $parsed['company_revenue'],
                    'company_location' => $parsed['company_location'],
                    'company_website' => $parsed['company_website'],
                    'company_description' => $parsed['company_description'],
                    'job_title' => $parsed['job_title'],
                    'job_seniority' => $parsed['job_seniority'],
                    'linkedin_url' => $parsed['linkedin_url'],
                    'twitter_url' => $parsed['twitter_url'],
                    'data_source' => 'clearbit',
                    'raw_data' => $enrichmentData,
                ]);

                // Update lead with enriched data
                $lead->update([
                    'first_name' => $parsed['first_name'] ?? $lead->first_name,
                    'last_name' => $parsed['last_name'] ?? $lead->last_name,
                    'company' => $parsed['company'] ?? $lead->company,
                    'status' => 'enriched'
                ]);
            });

            Log::info("Lead enrichment completed", ['lead_id' => $lead->id]);
            return true;

        } catch (\Exception $e) {
            Log::error("Lead enrichment failed", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            
            $lead->update(['status' => 'new']);
            return false;
        }
    }
}