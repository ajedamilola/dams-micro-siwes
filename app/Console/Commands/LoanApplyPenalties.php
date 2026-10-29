<?php

namespace App\Console\Commands;

use App\Enums\Loan\LoanStatus;
use App\Models\Loan\Loan;
use App\Services\Loan\LoanLifecycle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('loans:apply-penalties {--date= : As-of date Y-m-d, defaults to today}')]
#[Description('Apply overdue penalties for all active loans. Safe to re-run.')]
class LoanApplyPenalties extends Command
{
    public function handle(LoanLifecycle $lifecycle): int
    {
        $date = (string) ($this->option('date') ?: now()->toDateString());
        $total = '0';
        $loans = Loan::where('status', LoanStatus::Active->value)->get();

        foreach ($loans as $loan) {
            $total = bcadd($total, $lifecycle->assessPenalty($loan, $date), 2);
        }

        $this->info("Applied {$total} penalties as of {$date}.");

        return self::SUCCESS;
    }
}
