<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait ValidatesDateRange
{
    /**
     * Get the validation rules for a check-in / check-out date range.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function dateRangeRules(): array
    {
        return [
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
        ];
    }
}
