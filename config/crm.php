<?php

return [
    'dhaka_zones' => [
        'Dhanmondi', 'Gulshan', 'Banani', 'Uttara', 'Mirpur',
        'Mohammadpur', 'Bashundhara', 'Wari', 'Old Dhaka', 'Motijheel',
        'Tejgaon', 'Rampura', 'Badda', 'Khilgaon', 'Shyamoli', 'Other',
    ],

    'lost_reasons' => [
        'Budget Mismatch',
        'Area Not Served',
        'Service Not Available',
        'Unresponsive',
        'Chose Competitor',
        'Duplicate',
        'Not Eligible — Age',
        'Not Eligible — Condition',
        'Other',
    ],

    'non_clinical_keywords' => [],

    'age_bands' => ['0–5', '6–12', '13–17', '18–59', '60–74', '75+'],

    'lead_statuses' => [
        'New', 'Contacted', 'Qualified', 'Converted',
        'Waitlisted', 'Lost', 'Not Eligible',
    ],

    'lead_channels' => [
        'WhatsApp', 'Email', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other',
    ],

    'consent_statuses' => ['Unknown', 'Granted', 'Withdrawn'],

    'log_channels' => ['WhatsApp', 'Email', 'Phone', 'In-Person', 'Other'],

    'household_statuses' => ['Onboarding', 'Active', 'On Hold', 'Closed'],

    'primary_languages' => ['Bangla', 'English', 'Other'],

    'contact_roles' => ['Primary', 'Billing', 'Emergency'],

    'recipient_types' => ['Child', 'Adult', 'Senior'],

    'live_in_prefs' => ['live-in', 'live-out', 'either'],
];
