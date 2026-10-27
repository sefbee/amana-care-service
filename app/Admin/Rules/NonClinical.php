<?php

namespace App\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NonClinical implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $keywords = config('crm.non_clinical_keywords', []);

        if (empty($keywords)) {
            return;
        }

        $lowerValue = strtolower((string) $value);

        foreach ($keywords as $keyword) {
            if (str_contains($lowerValue, strtolower($keyword))) {
                $fail("The :attribute field contains restricted clinical terminology (\"{$keyword}\"). Please use plain, non-clinical language.");
                return;
            }
        }
    }
}
