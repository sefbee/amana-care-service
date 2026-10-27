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

    'non_clinical_keywords' => array_values(array_unique(array_filter(array_merge([
        'diagnosis', 'diagnoses', 'diagnosed', 'diabetes', 'alzheimer', 'prescription',
        'dosage', 'dose of', 'treatment protocol', 'medical treatment', 'metformin',
        'insulin', 'amlodipine', 'atorvastatin', 'losartan', 'warfarin', 'omeprazole',
        'salbutamol', 'paracetamol', 'acetaminophen', 'antibiotic',
    ], array_map('trim', explode(',', (string) env('CRM_NON_CLINICAL_KEYWORDS', ''))))))),

    'age_bands' => ['0–5', '6–12', '13–17', '18–59', '60–74', '75+'],

    'recipient_type_by_age_band' => [
        '0–5' => 'Child',
        '6–12' => 'Child',
        '13–17' => 'Child',
        '18–59' => 'Adult',
        '60–74' => 'Senior',
        '75+' => 'Senior',
    ],

    'lead_statuses' => [
        'New', 'Contacted', 'Qualified', 'Converted',
        'Waitlisted', 'Lost', 'Not Eligible',
    ],

    'lead_channels' => [
        'WhatsApp', 'Email', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other',
    ],

    'consent_statuses' => ['Unknown', 'Granted', 'Withdrawn'],

    'consent_channels' => ['WhatsApp', 'Email', 'SMS', 'Phone', 'Walk-in', 'Referral', 'Web Form', 'Other'],

    'log_channels' => ['WhatsApp', 'Email', 'SMS', 'Phone', 'In-Person', 'Other'],

    'household_statuses' => ['Onboarding', 'Active', 'On Hold', 'Closed'],

    'primary_languages' => ['Bangla', 'English', 'Other'],

    'contact_roles' => ['Primary', 'Billing', 'Emergency'],

    'recipient_types' => ['Child', 'Adult', 'Senior'],

    'live_in_prefs' => ['live-in', 'live-out', 'either'],

    'assessment_profiles' => [
        'NANNY' => [
            'number_of_children' => ['type' => 'integer', 'required' => true],
            'school_routines' => ['type' => 'string', 'required' => false],
            'play_habits' => ['type' => 'string', 'required' => false],
        ],
        'ELDERLY' => [
            'mobility_levels' => ['type' => 'string', 'required' => false],
            'companionship_needs' => ['type' => 'string', 'required' => false],
            'dietary_restrictions' => ['type' => 'string', 'required' => false],
        ],
    ],
];
