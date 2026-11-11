<?php

namespace App\Services\Loan;

use App\Enums\Loan\InterestMethod;
use App\Enums\Loan\LoanStatus;
use App\Enums\Loan\OperationType;
use App\Enums\Loan\RepaymentFrequency;
use App\Models\GeneralLedger\Account;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanRepayment;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Loan state machine: draft -> pending_approval -> approved -> active -> closed.
 * Every money movement creates and posts its journal to the general ledger
 * immediately, so no manual posting step is required for loan activity.
 */
class LoanLifecycle
{
    public function __construct(
        private ScheduleGenerator $schedules,
        private LoanPosting $posting,
    ) {}

    /**
     * @param  array{company_id: string, loan_product_id: string, loan_customer_id: string, principal: string, tenor: int, first_due_date: string, notes?: ?string}  $data
     */
    public function book(array $data): Loan
    {
        return DB::transaction(function () use ($data) {
            $product = LoanProduct::with('mappings')->findOrFail($data['loan_product_id']);
            $principal = number_format((float) $data['principal'], 2, '.', '');
            $tenor = (int) $data['tenor'];

            $this->assertWithinProduct($product, $principal, $tenor);

            $fee = bcdiv(bcmul($principal, (string) $product->processing_fee_rate, 6), '100', 2);

            $loan = Loan::create([
                'loan_no' => $this->nextLoanNo((string) $product->company_id),
                'company_id' => $product->company_id,
                'loan_product_id' => $product->id,
                'loan_customer_id' => $data['loan_customer_id'],
                'principal' => $principal,
                'annual_interest_rate' => $product->annual_interest_rate,
                'interest_method' => $product->interest_method,
                'repayment_frequency' => $product->repayment_frequency,
                'tenor' => $tenor,
                'processing_fee' => $fee,
                'first_due_date' => $data['first_due_date'],
                'status' => LoanStatus::PendingApproval->value,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->buildSchedules($loan);

            return $loan->refresh();
        });
    }

    public function approve(Loan $loan): Loan
    {
        if ($loan->status !== LoanStatus::PendingApproval->value && $loan->status !== LoanStatus::Draft->value) {
            throw ValidationException::withMessages(['status' => 'Only draft or pending loans can be approved.']);
        }
        DB::update(
            'update loans set status = ?, approved_at = ?, updated_at = ? where id = ?',
            [LoanStatus::Approved->value, now(), now(), $loan->id]
        );

        return $loan->refresh();
    }

    public function reject(Loan $loan): Loan
    {
        if (! in_array($loan->status, [LoanStatus::Draft->value, LoanStatus::PendingApproval->value], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or pending loans can be rejected.']);
        }
        DB::update(
            'update loans set status = ?, updated_at = ? where id = ?',
            [LoanStatus::Rejected->value, now(), $loan->id]
        );

        return $loan->refresh();
    }

    public function disburse(Loan $loan, string $bankAccountId, string $disbursementDate): Loan
    {
        if ($loan->status !== LoanStatus::Approved->value) {
            throw ValidationException::withMessages(['status' => 'Only approved loans can be disbursed.']);
        }

        return DB::transaction(function () use ($loan, $bankAccountId, $disbursementDate) {
            $product = $loan->product()->with('mappings.account')->firstOrFail();
            $bank = Account::findOrFail($bankAccountId);
            $feeIncome = $product->accountFor(OperationType::FeeIncome->value);

            $loan->update([
                'disbursement_account_id' => $bank->id,
                'disbursement_date' => $disbursementDate,
            ]);

            $txn = $this->posting->disbursement($loan->refresh(), $bank, $feeIncome);

            $maturity = $loan->schedules()->orderByDesc('installment_no')->value('due_date');
            $loan->update([
                'disbursement_journal_id' => $txn->id,
                'status' => LoanStatus::Active->value,
                'disbursed_at' => now(),
                'maturity_date' => $maturity,
            ]);

            return $loan->refresh();
        });
    }

    /**
     * FIFO allocation: penalty -> fee -> interest -> principal, oldest due first.
     */
    public function repay(Loan $loan, string $amount, string $paymentAccountId, string $paymentDate, ?string $notes = null): LoanRepayment
    {
        if ($loan->status !== LoanStatus::Active->value) {
            throw ValidationException::withMessages(['status' => 'Only active loans can receive repayments.']);
        }
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Repayment amount must be positive.']);
        }

        return DB::transaction(function () use ($loan, $amount, $paymentAccountId, $paymentDate, $notes) {
            $this->assessPenalty($loan->refresh(), $paymentDate);

            $remaining = number_format((float) $amount, 2, '.', '');
            $allocation = ['principal' => '0', 'interest' => '0', 'fee' => '0', 'penalty' => '0'];

            $schedules = $loan->schedules()->orderBy('installment_no')->lockForUpdate()->get();
            foreach ($schedules as $schedule) {
                if (bccomp($remaining, '0', 2) <= 0) {
                    break;
                }
                foreach (['penalty', 'fee', 'interest', 'principal'] as $bucket) {
                    $dueKey = "{$bucket}_due";
                    $paidKey = "{$bucket}_paid";
                    $owed = bcsub((string) $schedule->$dueKey, (string) $schedule->$paidKey, 2);
                    if (bccomp($owed, '0', 2) <= 0) {
                        continue;
                    }
                    $take = bccomp($remaining, $owed, 2) >= 0 ? $owed : $remaining;
                    $schedule->$paidKey = bcadd((string) $schedule->$paidKey, $take, 2);
                    $allocation[$bucket] = bcadd($allocation[$bucket], $take, 2);
                    $remaining = bcsub($remaining, $take, 2);
                    if (bccomp($remaining, '0', 2) <= 0) {
                        break;
                    }
                }
                $schedule->status = bccomp($schedule->remaining(), '0', 2) <= 0 ? 'paid' : (bccomp(bcadd(bcadd((string) $schedule->principal_paid, (string) $schedule->interest_paid, 2), bcadd((string) $schedule->fee_paid, (string) $schedule->penalty_paid, 2), 2), '0', 2) > 0 ? 'partial' : 'pending');
                $schedule->save();
            }

            $allocated = bcadd(bcadd($allocation['principal'], $allocation['interest'], 2), bcadd($allocation['fee'], $allocation['penalty'], 2), 2);
            $bank = Account::findOrFail($paymentAccountId);
            $txn = $this->posting->repayment($loan, $bank, $allocation, $paymentDate);

            $repayment = $loan->repayments()->create([
                'amount' => $allocated,
                'payment_date' => $paymentDate,
                'payment_account_id' => $bank->id,
                'principal_component' => $allocation['principal'],
                'interest_component' => $allocation['interest'],
                'fee_component' => $allocation['fee'],
                'penalty_component' => $allocation['penalty'],
                'journal_transaction_id' => $txn->id,
                'notes' => $notes,
            ]);

            if (bccomp($remaining, '0', 2) > 0) {
                // Overpayment is left as unallocated change; caller refunds it.
                $repayment->notes = trim(($notes ? $notes.' ' : '')."Overpayment change: {$remaining}.");
                $repayment->save();
            }

            $outstanding = $loan->refresh()->schedules->sum(fn ($s) => (float) $s->remaining());
            if ($outstanding <= 0.005) {
                $loan->update(['status' => LoanStatus::Closed->value, 'closed_at' => now()]);
            }

            return $repayment->refresh();
        });
    }

    /** Daily interest accrual up to each instalment's interest_due. Idempotent per day. */
    public function accrue(Loan $loan, string $date): int
    {
        if ($loan->status !== LoanStatus::Active->value) {
            return 0;
        }

        return DB::transaction(function () use ($loan, $date) {
            $product = $loan->product()->with('mappings.account')->firstOrFail();
            $receivable = $product->accountFor(OperationType::InterestReceivable->value)
                ?? throw ValidationException::withMessages(['mapping' => 'Interest receivable is not mapped for this product.']);
            $income = $product->accountFor(OperationType::InterestIncome->value)
                ?? throw ValidationException::withMessages(['mapping' => 'Interest income is not mapped for this product.']);

            $count = 0;
            $schedules = $loan->schedules()->orderBy('installment_no')->lockForUpdate()->get();
            $prevDate = CarbonImmutable::parse($loan->disbursement_date?->toDateString() ?? $loan->first_due_date->toDateString());

            foreach ($schedules as $schedule) {
                $due = CarbonImmutable::parse($schedule->due_date->toDateString());
                $asOf = CarbonImmutable::parse($date);
                if ($asOf->lt($prevDate)) {
                    $prevDate = $due;

                    continue;
                }
                $periodDays = max(1, $prevDate->diffInDays($due));
                $elapsed = min($periodDays, max(0, $prevDate->diffInDays(min($asOf, $due))));
                $earned = bcdiv(bcmul((string) $schedule->interest_due, (string) $elapsed, 6), (string) $periodDays, 6);
                $earned = number_format((float) $earned, 2, '.', '');
                $toAccrue = bcsub($earned, (string) $schedule->accrued_interest, 2);

                if (bccomp($toAccrue, '0', 2) > 0 && $asOf->gte($prevDate)) {
                    if ($loan->accruals()->where('loan_schedule_id', $schedule->id)->where('accrual_date', $date)->exists()) {
                        $prevDate = $due;

                        continue;
                    }
                    $txn = $this->posting->accrual(
                        $receivable,
                        $income,
                        $toAccrue,
                        "Interest accrual {$loan->loan_no} #{$schedule->installment_no} {$date}",
                        $date
                    );
                    $loan->accruals()->create([
                        'loan_schedule_id' => $schedule->id,
                        'accrual_date' => $date,
                        'interest_amount' => $toAccrue,
                        'journal_transaction_id' => $txn->id,
                    ]);
                    $schedule->accrued_interest = bcadd((string) $schedule->accrued_interest, $toAccrue, 2);
                    $schedule->save();
                    $count++;
                }
                $prevDate = $due;
            }

            return $count;
        });
    }

    /** Applies overdue penalties as of a date. Safe to re-run: only the delta posts. */
    public function assessPenalty(Loan $loan, string $asOfDate): string
    {
        return DB::transaction(function () use ($loan, $asOfDate) {
            $product = $loan->product()->with('mappings.account')->firstOrFail();
            $rate = (string) $product->penalty_rate_daily;
            if (bccomp($rate, '0', 4) <= 0) {
                return '0';
            }
            $grace = (int) $product->penalty_grace_days;
            $receivable = $product->accountFor(OperationType::PenaltyReceivable->value);
            $income = $product->accountFor(OperationType::PenaltyIncome->value);
            $asOf = CarbonImmutable::parse($asOfDate);
            $totalNew = '0';

            $schedules = $loan->schedules()->orderBy('installment_no')->lockForUpdate()->get();
            foreach ($schedules as $schedule) {
                $due = CarbonImmutable::parse($schedule->due_date->toDateString())->addDays($grace);
                if ($asOf->lte($due)) {
                    continue;
                }
                if (bccomp($schedule->remaining(), '0', 2) <= 0) {
                    continue;
                }
                $days = $due->diffInDays($asOf);
                $unpaidPIF = bcsub(bcadd(bcadd((string) $schedule->principal_due, (string) $schedule->interest_due, 2), (string) $schedule->fee_due, 2), bcadd(bcadd((string) $schedule->principal_paid, (string) $schedule->interest_paid, 2), (string) $schedule->fee_paid, 2), 2);
                $overdueBase = $unpaidPIF;
                $should = bcdiv(bcmul(bcmul($overdueBase, $rate, 6), (string) $days, 6), '100', 6);
                $should = number_format((float) $should, 2, '.', '');
                $delta = bcsub($should, (string) $schedule->penalty_due, 2);
                if (bccomp($delta, '0', 2) <= 0) {
                    continue;
                }
                abort_unless($receivable && $income, 422, 'Penalty accounts are not mapped for this product.');
                $this->posting->accrual(
                    $receivable,
                    $income,
                    $delta,
                    "Penalty {$loan->loan_no} #{$schedule->installment_no} {$asOfDate}",
                    $asOfDate
                );
                $schedule->penalty_due = bcadd((string) $schedule->penalty_due, $delta, 2);
                $schedule->total_due = bcadd((string) $schedule->total_due, $delta, 2);
                $schedule->save();
                $totalNew = bcadd($totalNew, $delta, 2);
            }

            return $totalNew;
        });
    }

    private function buildSchedules(Loan $loan): void
    {
        $product = $loan->product()->firstOrFail();
        $rows = $this->schedules->generate(
            (string) $loan->principal,
            (string) $loan->annual_interest_rate,
            InterestMethod::from($loan->interest_method),
            RepaymentFrequency::from($loan->repayment_frequency),
            (int) $loan->tenor,
            $loan->first_due_date->toDateString(),
            (string) $loan->processing_fee,
        );

        $totalInterest = '0';
        foreach ($rows as $i => $row) {
            $totalInterest = bcadd($totalInterest, $row['interest_due'], 2);
            $loan->schedules()->create([
                'installment_no' => $i + 1,
                'due_date' => $row['due_date'],
                'principal_due' => $row['principal_due'],
                'interest_due' => $row['interest_due'],
                'fee_due' => $row['fee_due'],
                'penalty_due' => '0',
                'total_due' => $row['total_due'],
                'status' => 'pending',
            ]);
        }

        $loan->update([
            'total_interest' => $totalInterest,
            'total_repayable' => bcadd(bcadd((string) $loan->principal, $totalInterest, 2), (string) $loan->processing_fee, 2),
        ]);
    }

    private function assertWithinProduct(LoanProduct $product, string $principal, int $tenor): void
    {
        if (bccomp($principal, (string) $product->min_principal, 2) < 0 || bccomp($principal, (string) $product->max_principal, 2) > 0) {
            throw ValidationException::withMessages(['principal' => "Principal must be between {$product->min_principal} and {$product->max_principal}."]);
        }
        if ($tenor < (int) $product->min_tenor || $tenor > (int) $product->max_tenor) {
            throw ValidationException::withMessages(['tenor' => "Tenor must be between {$product->min_tenor} and {$product->max_tenor} instalments."]);
        }
    }

    private function nextLoanNo(string $companyId): string
    {
        $year = BusinessDate::current()->format('Y');
        $prefix = "LN-{$year}-";
        $max = Loan::where('company_id', $companyId)->where('loan_no', 'like', "{$prefix}%")->orderByDesc('loan_no')->value('loan_no');
        $next = 1;
        if ($max && preg_match('/(\d+)$/', $max, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
