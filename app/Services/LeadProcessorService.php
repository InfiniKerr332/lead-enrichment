<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class LeadProcessorService
{
    private $enrichmentService;
    private $scoringService;
    private $assignmentService;
    private $emailService;

    public function __construct(
        LeadEnrichmentService $enrichmentService,
        LeadScoringService $scoringService,
        LeadAssignmentService $assignmentService,
        EmailSequenceService $emailService
    ) {
        $this->enrichmentService = $enrichmentService;
        $this->scoringService = $scoringService;
        $this->assignmentService = $assignmentService;
        $this->emailService = $emailService;
    }

    public function processLead(Lead $lead)
    {
        Log::info("Processing lead", ['lead_id' => $lead->id]);

        try {
            // Step 1: Enrich the lead
            $enriched = $this->enrichmentService->enrichLead($lead);
            if (!$enriched) {
                Log::warning("Lead enrichment failed, stopping process", ['lead_id' => $lead->id]);
                return false;
            }

            // Step 2: Calculate lead score
            $score = $this->scoringService->calculateScore($lead);
            if (!$score) {
                Log::warning("Lead scoring failed, stopping process", ['lead_id' => $lead->id]);
                return false;
            }

            // Step 3: Assign to sales rep (if qualified)
            if (in_array($score['grade'], ['A', 'B'])) {
                $this->assignmentService->assignLead($lead);
            }

            // Step 4: Enroll in email sequence
            $this->emailService->enrollLeadInSequence($lead);

            Log::info("Lead processing completed successfully", [
                'lead_id' => $lead->id,
                'grade' => $score['grade'],
                'total_score' => $score['total']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Lead processing failed", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function processNewLeads()
    {
        $leads = Lead::where('status', 'new')
            ->limit(50)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($leads as $lead) {
            $result = $this->processLead($lead);
            if ($result) {
                $processed++;
            } else {
                $failed++;
            }

            // Rate limiting - wait 1 second between API calls
            sleep(1);
        }

        Log::info("Batch processing completed", [
            'processed' => $processed,
            'failed' => $failed
        ]);

        return [
            'processed' => $processed,
            'failed' => $failed
        ];
    }
}