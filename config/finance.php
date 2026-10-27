<?php

return [
    // Must be explicitly approved/configured; supported value is monthly_in_advance.
    'billing_cycle' => env('FINANCE_BILLING_CYCLE'),
    // Recurring quote-override treatment is unresolved; no automatic drafts until approved.
    'recurring_amount_basis' => env('FINANCE_RECURRING_AMOUNT_BASIS'),
];
