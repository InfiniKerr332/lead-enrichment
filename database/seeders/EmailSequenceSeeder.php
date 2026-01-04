<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailSequence;
use App\Models\EmailTemplate;

class EmailSequenceSeeder extends Seeder
{
    public function run(): void
    {
        // A-Grade Sequence (Hot Leads)
        $sequenceA = EmailSequence::create([
            'name' => 'Hot Lead Follow-up',
            'description' => 'Aggressive follow-up for A-grade leads',
            'lead_grade' => 'A',
            'active' => true
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceA->id,
            'step_number' => 1,
            'delay_days' => 0,
            'subject' => 'Great to connect, {{first_name}}!',
            'body' => "Hi {{first_name}},\n\nI noticed you've been exploring our solutions. I'd love to schedule a quick call to discuss how we can help {{company}} achieve its goals.\n\nAre you available for a 15-minute call this week?\n\nBest regards"
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceA->id,
            'step_number' => 2,
            'delay_days' => 2,
            'subject' => 'Quick follow-up for {{company}}',
            'body' => "Hi {{first_name}},\n\nI wanted to follow up on my previous email. I have some ideas specific to {{company}} that I think you'll find valuable.\n\nWould tomorrow or Thursday work for a brief call?\n\nBest regards"
        ]);

        // B-Grade Sequence (Warm Leads)
        $sequenceB = EmailSequence::create([
            'name' => 'Warm Lead Nurture',
            'description' => 'Moderate follow-up for B-grade leads',
            'lead_grade' => 'B',
            'active' => true
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceB->id,
            'step_number' => 1,
            'delay_days' => 1,
            'subject' => 'Thanks for your interest, {{first_name}}',
            'body' => "Hi {{first_name}},\n\nThank you for your interest in our solutions. I'd love to learn more about {{company}}'s needs.\n\nWould you be open to a conversation next week?\n\nBest regards"
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceB->id,
            'step_number' => 2,
            'delay_days' => 5,
            'subject' => 'Resources for {{company}}',
            'body' => "Hi {{first_name}},\n\nI wanted to share some resources that might be helpful for {{company}}.\n\nLet me know if you'd like to discuss further!\n\nBest regards"
        ]);

        // C-Grade Sequence (Cold Leads)
        $sequenceC = EmailSequence::create([
            'name' => 'Cold Lead Nurture',
            'description' => 'Long-term nurture for C-grade leads',
            'lead_grade' => 'C',
            'active' => true
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceC->id,
            'step_number' => 1,
            'delay_days' => 3,
            'subject' => 'Industry insights for {{first_name}}',
            'body' => "Hi {{first_name}},\n\nI thought you might find these industry insights relevant to {{company}}.\n\nFeel free to reach out if you'd like to learn more.\n\nBest regards"
        ]);

        // D-Grade Sequence (Low Priority)
        $sequenceD = EmailSequence::create([
            'name' => 'Low Priority Nurture',
            'description' => 'Minimal touch for D-grade leads',
            'lead_grade' => 'D',
            'active' => true
        ]);

        EmailTemplate::create([
            'sequence_id' => $sequenceD->id,
            'step_number' => 1,
            'delay_days' => 7,
            'subject' => 'Stay connected with us',
            'body' => "Hi {{first_name}},\n\nWe're here if you ever need our help. Check out our latest resources.\n\nBest regards"
        ]);
    }
}