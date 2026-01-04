<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Services\LeadScoringService;

class RecalculateScores extends Command
{
    protected $signature = 'leads:recalculate-scores';
    protected $description = 'Recalculate scores for all leads with new activities';

    public function handle(LeadScoringService $scoringService)
    {
        $this->info('Recalculating lead scores...');

        $leads = Lead::whereIn('status', ['enriched', 'assigned', 'contacted'])
            ->with('activities')
            ->get();

        $updated = 0;

        foreach ($leads as $lead) {
            $scoringService->calculateScore($lead);
            $updated++;
        }

        $this->info("✓ Updated {$updated} lead scores");

        return Command::SUCCESS;
    }
}