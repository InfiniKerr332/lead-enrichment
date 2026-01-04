<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadScore;
use Illuminate\Support\Facades\Log;

class LeadScoringService
{
    private $scoringRules;

    public function __construct()
    {
        $this->scoringRules = config('scoring');
    }

    public function calculateScore(Lead $lead)
    {
        try {
            $firmographicScore = $this->calculateFirmographicScore($lead);
            $behavioralScore = $this->calculateBehavioralScore($lead);
            $totalScore = $firmographicScore + $behavioralScore;
            $grade = $this->getGrade($totalScore);

            // Create or update score
            LeadScore::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'firmographic_score' => $firmographicScore,
                    'behavioral_score' => $behavioralScore,
                    'total_score' => $totalScore,
                    'grade' => $grade,
                    'calculated_at' => now()
                ]
            );

            Log::info("Lead scored", [
                'lead_id' => $lead->id,
                'total_score' => $totalScore,
                'grade' => $grade
            ]);

            return [
                'firmographic' => $firmographicScore,
                'behavioral' => $behavioralScore,
                'total' => $totalScore,
                'grade' => $grade
            ];

        } catch (\Exception $e) {
            Log::error("Lead scoring failed", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function calculateFirmographicScore(Lead $lead)
    {
        $enrichment = $lead->enrichment;
        if (!$enrichment) return 0;

        $score = 0;

        // Company size score
        $score += $this->scoringRules['company_size'][$enrichment->company_size] ?? 0;

        // Industry score
        $score += $this->scoringRules['industries'][$enrichment->company_industry] 
            ?? $this->scoringRules['industries']['default'];

        // Revenue score
        $score += $this->scoringRules['revenue'][$enrichment->company_revenue] ?? 0;

        return $score;
    }

    private function calculateBehavioralScore(Lead $lead)
    {
        $activities = $lead->activities()
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->get();

        $score = 0;

        foreach ($activities as $activity) {
            $points = $this->scoringRules['activities'][$activity->activity_type] ?? 0;
            $score += $points * $activity->count;
        }

        return $score;
    }

    private function getGrade($totalScore)
    {
        $grades = $this->scoringRules['grades'];
        
        if ($totalScore >= $grades['A']) return 'A';
        if ($totalScore >= $grades['B']) return 'B';
        if ($totalScore >= $grades['C']) return 'C';
        return 'D';
    }

    public function trackActivity(Lead $lead, $activityType, $activityValue = null)
    {
        $points = $this->scoringRules['activities'][$activityType] ?? 0;

        $lead->activities()->create([
            'activity_type' => $activityType,
            'activity_value' => $activityValue,
            'points' => $points
        ]);

        // Recalculate score
        $this->calculateScore($lead);
    }
}