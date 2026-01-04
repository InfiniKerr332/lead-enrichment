<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\EmailSequence;
use App\Models\EmailQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EmailSequenceService
{
    public function enrollLeadInSequence(Lead $lead)
    {
        try {
            $score = $lead->score;
            if (!$score) return false;

            // Find appropriate sequence
            $sequence = EmailSequence::where('active', true)
                ->where('lead_grade', $score->grade)
                ->first();

            if (!$sequence) {
                Log::info("No email sequence found for grade {$score->grade}");
                return false;
            }

            // Get all templates
            $templates = $sequence->templates()
                ->orderBy('step_number')
                ->get();

            if ($templates->isEmpty()) {
                Log::warning("Email sequence has no templates", [
                    'sequence_id' => $sequence->id
                ]);
                return false;
            }

            // Schedule all emails
            $now = Carbon::now();
            foreach ($templates as $template) {
                $scheduledAt = $now->copy()->addDays($template->delay_days);

                EmailQueue::create([
                    'lead_id' => $lead->id,
                    'template_id' => $template->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending'
                ]);
            }

            Log::info("Lead enrolled in email sequence", [
                'lead_id' => $lead->id,
                'sequence_id' => $sequence->id,
                'emails_scheduled' => $templates->count()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Email sequence enrollment failed", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function processEmailQueue()
    {
        $emails = EmailQueue::with(['lead', 'template'])
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->limit(50)
            ->get();

        $processed = 0;

        foreach ($emails as $emailItem) {
            try {
                $this->sendEmail($emailItem);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to send email", [
                    'email_queue_id' => $emailItem->id,
                    'error' => $e->getMessage()
                ]);

                $emailItem->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        Log::info("Email queue processed", ['emails_sent' => $processed]);
        return $processed;
    }

    private function sendEmail($emailItem)
    {
        $lead = $emailItem->lead;
        $template = $emailItem->template;

        $subject = $this->personalizeContent($template->subject, $lead);
        $body = $this->personalizeContent($template->body, $lead);

        Mail::raw($body, function ($message) use ($lead, $subject) {
            $message->to($lead->email)
                ->subject($subject)
                ->from(config('mail.from.address'), config('mail.from.name'));
        });

        $emailItem->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

        Log::info("Email sent", [
            'email_queue_id' => $emailItem->id,
            'lead_id' => $lead->id
        ]);
    }

    private function personalizeContent($content, $lead)
    {
        $replacements = [
            '{{first_name}}' => $lead->first_name,
            '{{last_name}}' => $lead->last_name,
            '{{full_name}}' => $lead->full_name,
            '{{company}}' => $lead->company,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }
}