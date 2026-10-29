<?php

namespace App\Console\Commands;

use App\Enums\Loan\LoanStatus;
use App\Models\Loan\Loan;
use App\Services\Loan\LoanLifecycle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:accrue-interest {--date= : Accrual date Y-m-d, defaults to today}')]
#[Description('Accrue daily loan interest for all active loans. Idempotent per day.')]
class LoanAccrueInterest extends Command
{
    public function handle(LoanLifecycle $lifecycle): int
    {
        $date = (string) ($this->option('date') ?: now()->toDateString());
        $count = 0;
        $loans = Loan::where('status', LoanStatus::Active->value)->get();

        foreach ($loans as $loan) {
            $count += $lifecycle->accrue($loan, $date);
        }

        $this->info("Accrued {$count} schedule(s) for {$date}.");

        return self::SUCCESS;
    }
}
