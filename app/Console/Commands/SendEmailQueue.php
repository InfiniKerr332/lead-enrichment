<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailSequenceService;

class SendEmailQueue extends Command
{
    protected $signature = 'emails:send';
    protected $description = 'Send scheduled emails from the queue';

    public function handle(EmailSequenceService $emailService)
    {
        $this->info('Processing email queue...');

        $sent = $emailService->processEmailQueue();

        $this->info("✓ Sent {$sent} emails");

        return Command::SUCCESS;
    }
}