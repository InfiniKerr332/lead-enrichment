<?php

return [
    'company_size' => [
        '1-10' => 5,
        '11-50' => 10,
        '51-200' => 15,
        '200+' => 20,
    ],

    'revenue' => [
        '<$1M' => 5,
        '$1M-$10M' => 15,
        '$10M+' => 25,
    ],

    'industries' => [
        'Technology' => 20,
        'Software' => 20,
        'SaaS' => 20,
        'Finance' => 18,
        'Financial Services' => 18,
        'Banking' => 18,
        'Healthcare' => 15,
        'E-commerce' => 15,
        'Retail' => 12,
        'Manufacturing' => 12,
        'Education' => 10,
        'Real Estate' => 10,
        'default' => 5,
    ],

    'activities' => [
        'pricing_page_view' => 15,
        'whitepaper_download' => 10,
        'demo_video_watch' => 20,
        'demo_request' => 25,
        'contact_form_submit' => 25,
        'email_open' => 2,
        'email_link_click' => 5,
        'case_study_view' => 8,
        'product_page_view' => 6,
        'blog_read' => 3,
        'webinar_registration' => 15,
        'trial_signup' => 30,
    ],

    'grades' => [
        'A' => 80,  // Hot lead - immediate follow-up
        'B' => 60,  // Warm lead - follow-up within 24 hours
        'C' => 40,  // Cold lead - nurture campaign
        'D' => 0,   // Low priority - automated nurture only
    ],
];