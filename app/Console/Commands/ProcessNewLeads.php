<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LeadProcessorService;

class ProcessNewLeads extends Command
{
    protected $signature = 'leads:process';
    protected $description = 'Process new leads: enrich, score, assign, and enroll in email sequences';

    public function handle(LeadProcessorService $processor)
    {
        $this->info('Starting to process new leads...');

        $results = $processor->processNewLeads();

        $this->info("Processing complete!");
        $this->info("✓ Processed: {$results['processed']}");
        $this->info("✗ Failed: {$results['failed']}");

        return Command::SUCCESS;
    }
}