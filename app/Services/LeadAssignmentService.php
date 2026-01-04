<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SalesRep;
use App\Models\LeadAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LeadAssignmentService
{
    public function assignLead(Lead $lead)
    {
        try {
            $score = $lead->score;
            $enrichment = $lead->enrichment;

            if (!$score) {
                Log::warning("Cannot assign lead without score", ['lead_id' => $lead->id]);
                return false;
            }

            // Only assign A and B grade leads automatically
            if (!in_array($score->grade, ['A', 'B'])) {
                Log::info("Lead grade {$score->grade} not eligible for auto-assignment", [
                    'lead_id' => $lead->id
                ]);
                return false;
            }

            // Find best sales rep
            $salesRep = $this->findBestSalesRep($enrichment, $score);

            if (!$salesRep) {
                Log::warning("No available sales rep found", ['lead_id' => $lead->id]);
                return false;
            }

            // Assign lead
            DB::transaction(function () use ($lead, $salesRep) {
                LeadAssignment::create([
                    'lead_id' => $lead->id,
                    'sales_rep_id' => $salesRep->id,
                    'status' => 'assigned'
                ]);

                $lead->update(['status' => 'assigned']);

                $salesRep->increment('current_load');
            });

            // Notify sales rep
            $this->notifySalesRep($salesRep, $lead);

            Log::info("Lead assigned", [
                'lead_id' => $lead->id,
                'sales_rep_id' => $salesRep->id
            ]);

            return $salesRep;

        } catch (\Exception $e) {
            Log::error("Lead assignment failed", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function findBestSalesRep($enrichment, $score)
    {
        $reps = SalesRep::where('active', true)
            ->whereRaw('current_load < capacity')
            ->get();

        if ($reps->isEmpty()) {
            return null;
        }

        $bestRep = null;
        $highestScore = -1;

        foreach ($reps as $rep) {
            $repScore = 0;
            $specialties = $rep->specialties ?? [];

            // Check industry specialization
            if (isset($specialties['industries']) && 
                $enrichment && 
                in_array($enrichment->company_industry, $specialties['industries'])) {
                $repScore += 10;
            }

            // Check location specialization
            if (isset($specialties['regions']) && 
                $enrichment && 
                in_array($enrichment->company_location, $specialties['regions'])) {
                $repScore += 5;
            }

            // Factor in current workload (prefer less loaded reps)
            $loadFactor = $rep->capacity > 0 
                ? ($rep->capacity - $rep->current_load) / $rep->capacity 
                : 0;
            $repScore += $loadFactor * 10;

            if ($repScore > $highestScore) {
                $highestScore = $repScore;
                $bestRep = $rep;
            }
        }

        return $bestRep;
    }

    private function notifySalesRep($rep, $lead)
    {
        Log::info("Sales rep notified", [
            'rep_id' => $rep->id,
            'lead_id' => $lead->id
        ]);
        
        // TODO: Implement actual email notification later
        // For now, just log it
    }
}