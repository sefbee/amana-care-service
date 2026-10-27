<?php

return [
    'storage_disk' => env('CAREGIVER_VAULT_DISK', 'caregiver-vault'),
    'quarantine_disk' => env('CAREGIVER_QUARANTINE_DISK', 'caregiver-quarantine'),
    'nid_hash_key' => env('CAREGIVER_NID_HASH_KEY'),
    'scanner_binary' => env('CLAMAV_BINARY', 'clamscan'),
    'vault_role_arn' => env('CAREGIVER_VAULT_ROLE_ARN'),
    'skills' => [
        'cpr_certified' => 'CPR Certified',
        'dementia_care_experience' => 'Dementia Care Experience',
        'newborn_experience' => 'Newborn Experience',
    ],
    'reveal_reasons' => [
        'verification_audit' => 'Verification Audit',
        'screening_review' => 'Screening Review',
        'safeguarding_review' => 'Safeguarding Review',
    ],
];
