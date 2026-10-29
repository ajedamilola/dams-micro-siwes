<?php

namespace App\Services\Loan;

use App\Enums\GeneralLedger\TransactionStatus;
use App\Enums\Loan\OperationType;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates and posts balanced journal transactions for loan events.
 *
 * Posting model (v1):
 * - Disbursement: Dr Bank (P) / Cr Customer (P - fee) / Cr FeeIncome (fee).
 *   The customer receives the principal net of the processing fee, and the
 *   fee is recognised as income at disbursement.
 * - Accrual: Dr InterestReceivable / Cr InterestIncome.
 * - Penalty: Dr PenaltyReceivable / Cr PenaltyIncome.
 * - Repayment: Dr Bank / Cr Customer (principal+fee) / Cr InterestReceivable
 *   (interest) / Cr PenaltyReceivable (penalty).
 * Product principal_receivable mapping is informational (portfolio
 * reporting; customer accounts roll to 1100 Customer Control).
 */
class LoanPosting
{
    public function disbursement(Loan $loan, Account $bankAccount, ?Account $feeIncomeAccount): JournalTransaction
    {
        $customer = $loan->customer()->firstOrFail();
        $customerAccount = $customer->account()->firstOrFail();

        $this->assertPostable($customerAccount);
        $this->assertPostable($bankAccount);
        if ($feeIncomeAccount) {
            $this->assertPostable($feeIncomeAccount);
        }

        $principal = (string) $loan->principal;
        $fee = (string) $loan->processing_fee;

        if (bccomp($fee, $principal, 2) >= 0) {
            throw ValidationException::withMessages([
                'processing_fee' => 'The processing fee must be less than the principal.',
            ]);
        }

        $netProceeds = bcsub($principal, $fee, 2);

        return DB::transaction(function () use ($loan, $customerAccount, $bankAccount, $feeIncomeAccount, $principal, $fee, $netProceeds) {
            $txn = JournalTransaction::create([
                'description' => "Loan disbursement {$loan->loan_no}",
                'ref' => $this->nextRef(),
                'status' => TransactionStatus::DRAFT->value,
                'effective_date' => $loan->disbursement_date?->toDateString() ?? BusinessDate::today(),
            ]);

            $txn->entries()->create([
                'account_id' => $bankAccount->id,
                'type' => 'debit',
                'value' => $principal,
                'balance_after' => 0,
            ]);
            if (bccomp($netProceeds, '0', 2) > 0) {
                $txn->entries()->create([
                    'account_id' => $customerAccount->id,
                    'type' => 'credit',
                    'value' => $netProceeds,
                    'balance_after' => 0,
                ]);
            }
            if (bccomp($fee, '0', 2) > 0) {
                abort_unless($feeIncomeAccount !== null, 422, 'Fee income account is not mapped for this product.');
                $txn->entries()->create([
                    'account_id' => $feeIncomeAccount->id,
                    'type' => 'credit',
                    'value' => $fee,
                    'balance_after' => 0,
                ]);
            }

            // Server-generated loan journals bypass the manual review
            // step and post to the ledger immediately.
            $txn->status = TransactionStatus::SUBMITTED->value;
            $txn->save();
            $txn->postTransaction();

            return $txn->refresh();
        });
    }

    public function accrual(Account $receivable, Account $income, string $amount, string $description, string $date): JournalTransaction
    {
        $this->assertPostable($receivable);
        $this->assertPostable($income);

        return DB::transaction(function () use ($receivable, $income, $amount, $description, $date) {
            $txn = JournalTransaction::create([
                'description' => $description,
                'ref' => $this->nextRef(),
                'status' => TransactionStatus::DRAFT->value,
                'effective_date' => $date,
            ]);
            $txn->entries()->create(['account_id' => $receivable->id, 'type' => 'debit', 'value' => $amount, 'balance_after' => 0]);
            $txn->entries()->create(['account_id' => $income->id, 'type' => 'credit', 'value' => $amount, 'balance_after' => 0]);
            // Server-generated loan journals bypass the manual review
            // step and post to the ledger immediately.
            $txn->status = TransactionStatus::SUBMITTED->value;
            $txn->save();
            $txn->postTransaction();

            return $txn->refresh();
        });
    }

    /**
     * @param  array{principal: string, interest: string, fee: string, penalty: string}  $allocation
     */
    public function repayment(Loan $loan, Account $bankAccount, array $allocation, string $paymentDate): JournalTransaction
    {
        $customer = $loan->customer()->firstOrFail();
        $customerAccount = $customer->account()->firstOrFail();
        $this->assertPostable($customerAccount);
        $this->assertPostable($bankAccount);

        $product = $loan->product()->with('mappings.account')->firstOrFail();
        $interestReceivable = $product->accountFor(OperationType::InterestReceivable->value);
        $penaltyReceivable = $product->accountFor(OperationType::PenaltyReceivable->value);
        if ($interestReceivable) {
            $this->assertPostable($interestReceivable);
        }
        if ($penaltyReceivable) {
            $this->assertPostable($penaltyReceivable);
        }

        $customerCredit = bcadd((string) $allocation['principal'], (string) $allocation['fee'], 2);
        $total = bcadd(bcadd($customerCredit, (string) $allocation['interest'], 2), (string) $allocation['penalty'], 2);

        return DB::transaction(function () use ($loan, $bankAccount, $customerAccount, $interestReceivable, $penaltyReceivable, $allocation, $customerCredit, $total, $paymentDate) {
            $txn = JournalTransaction::create([
                'description' => "Loan repayment {$loan->loan_no}",
                'ref' => $this->nextRef(),
                'status' => TransactionStatus::DRAFT->value,
                'effective_date' => $paymentDate,
            ]);
            $txn->entries()->create(['account_id' => $bankAccount->id, 'type' => 'debit', 'value' => $total, 'balance_after' => 0]);
            if (bccomp($customerCredit, '0', 2) > 0) {
                $txn->entries()->create(['account_id' => $customerAccount->id, 'type' => 'credit', 'value' => $customerCredit, 'balance_after' => 0]);
            }
            if (bccomp((string) $allocation['interest'], '0', 2) > 0) {
                abort_unless($interestReceivable !== null, 422, 'Interest receivable is not mapped for this product.');
                $txn->entries()->create(['account_id' => $interestReceivable->id, 'type' => 'credit', 'value' => (string) $allocation['interest'], 'balance_after' => 0]);
            }
            if (bccomp((string) $allocation['penalty'], '0', 2) > 0) {
                abort_unless($penaltyReceivable !== null, 422, 'Penalty receivable is not mapped for this product.');
                $txn->entries()->create(['account_id' => $penaltyReceivable->id, 'type' => 'credit', 'value' => (string) $allocation['penalty'], 'balance_after' => 0]);
            }

            // Server-generated loan journals bypass the manual review
            // step and post to the ledger immediately.
            $txn->status = TransactionStatus::SUBMITTED->value;
            $txn->save();
            $txn->postTransaction();

            return $txn->refresh();
        });
    }

    public function mappedAccount(LoanProduct $product, OperationType $type): Account
    {
        $account = $product->accountFor($type->value);
        abort_unless($account !== null, 422, "Operation {$type->value} is not mapped for product {$product->code}.");
        $this->assertPostable($account);

        return $account;
    }

    public function assertPostable(Account $account): void
    {
        if (($account->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages(['account_id' => 'Entries can only post to active accounts.']);
        }
        if ($account->subledger_id !== null || Account::where('parent_id', $account->id)->exists()) {
            throw ValidationException::withMessages(['account_id' => "Account {$account->code} is a group/control account. Map a leaf account instead."]);
        }
    }

    public function assertMapping(Account $account, OperationType $type): void
    {
        $expected = OperationType::expectations()[$type->value];
        if ($account->balance_type !== $expected['balance_type']) {
            throw ValidationException::withMessages([
                'account_id' => "Account {$account->code} must be {$expected['balance_type']} for {$type->value}.",
            ]);
        }
        if (! in_array($account->category, $expected['categories'], true)) {
            throw ValidationException::withMessages([
                'account_id' => "Account {$account->code} category {$account->category} is not valid for {$type->value}.",
            ]);
        }
        $this->assertPostable($account);
    }

    private function nextRef(): string
    {
        $max = JournalTransaction::where('ref', 'like', 'TXN-%')
            ->pluck('ref')
            ->map(fn (string $ref) => (int) preg_replace('/\D+/', '', substr($ref, 4)) ?: 0)
            ->max() ?? 0;

        return sprintf('TXN-%04d', $max + 1);
    }
}
