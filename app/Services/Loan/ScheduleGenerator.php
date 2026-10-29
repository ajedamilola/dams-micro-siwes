<?php

namespace App\Services\Loan;

use App\Enums\Loan\InterestMethod;
use App\Enums\Loan\RepaymentFrequency;
use Carbon\CarbonImmutable;

/**
 * Builds equal-instalment repayment schedules.
 *
 * Flat: total interest = P * annual_rate * tenor / periods_per_year,
 * split equally. Reducing: standard EMI amortisation with the
 * periodic rate = annual_rate / periods_per_year.
 */
class ScheduleGenerator
{
    /**
     * @return array<int, array{due_date: string, principal_due: string, interest_due: string, fee_due: string, total_due: string}>
     */
    public function generate(
        string $principal,
        string $annualRate,
        InterestMethod $method,
        RepaymentFrequency $frequency,
        int $tenor,
        string $firstDueDate,
        string $processingFee = '0',
    ): array {
        $periodsPerYear = $frequency->periodsPerYear();
        $periodicRate = ((float) $annualRate) / 100 / $periodsPerYear;

        $principalCents = (int) round(((float) $principal) * 100);
        $feeCents = (int) round(((float) $processingFee) * 100);

        if ($method === InterestMethod::Flat) {
            $totalInterestCents = (int) round($principalCents * $periodicRate * $tenor);
            $baseInterest = intdiv($totalInterestCents, $tenor);
            $interestParts = array_fill(0, $tenor, $baseInterest);
            $interestParts[$tenor - 1] += $totalInterestCents - $baseInterest * $tenor;
        } else {
            $interestParts = $this->reducingInterestParts($principalCents, $periodicRate, $tenor);
        }

        $basePrincipal = intdiv($principalCents, $tenor);
        $rows = [];
        $date = CarbonImmutable::parse($firstDueDate);

        for ($i = 1; $i <= $tenor; $i++) {
            $principalDue = $i === $tenor
                ? $principalCents - $basePrincipal * ($tenor - 1)
                : $basePrincipal;

            $rows[] = [
                'due_date' => $date->toDateString(),
                'principal_due' => number_format($principalDue / 100, 2, '.', ''),
                'interest_due' => number_format($interestParts[$i - 1] / 100, 2, '.', ''),
                'fee_due' => number_format($i === 1 ? $feeCents / 100 : 0, 2, '.', ''),
                'total_due' => number_format(($principalDue + $interestParts[$i - 1] + ($i === 1 ? $feeCents : 0)) / 100, 2, '.', ''),
            ];

            $date = $frequency->addTo($date);
        }

        return $rows;
    }

    /** @return array<int, int> interest cents per instalment. */
    private function reducingInterestParts(int $principalCents, float $periodicRate, int $tenor): array
    {
        if ($periodicRate <= 0) {
            return array_fill(0, $tenor, 0);
        }

        $factor = pow(1 + $periodicRate, $tenor);
        $emi = (int) round($principalCents * $periodicRate * $factor / ($factor - 1));

        $parts = [];
        $balance = $principalCents;
        for ($i = 1; $i <= $tenor; $i++) {
            $interest = (int) round($balance * $periodicRate);
            if ($i === $tenor) {
                $parts[] = $interest;
            } else {
                $parts[] = $interest;
                $balance -= ($emi - $interest);
            }
        }

        return $parts;
    }
}
