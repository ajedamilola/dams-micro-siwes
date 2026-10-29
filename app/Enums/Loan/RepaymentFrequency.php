<?php

namespace App\Enums\Loan;

use Carbon\CarbonImmutable;

enum RepaymentFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function periodsPerYear(): int
    {
        return match ($this) {
            self::Weekly => 52,
            self::Biweekly => 26,
            self::Monthly => 12,
        };
    }

    public function addTo(CarbonImmutable $date, int $n = 1): CarbonImmutable
    {
        return match ($this) {
            self::Weekly => $date->addWeeks($n),
            self::Biweekly => $date->addWeeks(2 * $n),
            self::Monthly => $date->addMonthsNoOverflow($n),
        };
    }
}
