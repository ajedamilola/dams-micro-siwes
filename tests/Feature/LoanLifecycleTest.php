<?php

use App\Enums\Loan\InterestMethod;
use App\Enums\Loan\RepaymentFrequency;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\Loan;
use App\Models\Loan\LoanCustomer;
use App\Models\Loan\LoanProduct;
use App\Services\Loan\ScheduleGenerator;
use Carbon\Carbon;

function seedLifecycleCompany(): array
{
    $company = Company::create(['name' => 'Lifecycle Co', 'code' => 'LCC']);
    $master = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);
    $customers = Ledger::create(['name' => 'Customers', 'code' => 'CUST', 'company_id' => $company->id]);

    $make = function (string $code, string $name, string $category, string $balance, ?string $parent = null) use ($master) {
        $parentId = $parent ? Account::where('ledger_id', $master->id)->where('code', $parent)->value('id') : null;

        return Account::create([
            'name' => $name, 'code' => $code, 'description' => $name,
            'ledger_id' => $master->id, 'parent_id' => $parentId,
            'balance_type' => $balance, 'category' => $category, 'status' => 'active',
        ]);
    };

    $bank = $make('1001', 'Cash on Hand', 'CashAndCashEquivalents', 'debit');
    $control = $make('1100', 'Customer Control', 'AccountsReceivable', 'debit');
    $control->update(['subledger_id' => $customers->id]);
    $make('1110', 'Loan Principal Portfolio', 'AccountsReceivable', 'debit');
    $interestRec = $make('1120', 'Interest Receivable - Loans', 'AccountsReceivable', 'debit');
    $penaltyRec = $make('1130', 'Penalty Receivable - Loans', 'AccountsReceivable', 'debit');
    $make('4000', 'Operating Revenue', 'OperatingRevenue', 'credit');
    $interestInc = $make('4003', 'Loan Interest Income', 'OperatingRevenue', 'credit', '4000');
    $feeInc = $make('4004', 'Loan Fee Income', 'OperatingRevenue', 'credit', '4000');

    $product = LoanProduct::create([
        'company_id' => $company->id, 'code' => 'SAL', 'name' => 'Salary Loan',
        'interest_method' => 'flat', 'annual_interest_rate' => 36,
        'repayment_frequency' => 'monthly', 'min_principal' => 10000,
        'max_principal' => 1000000, 'min_tenor' => 1, 'max_tenor' => 12,
        'processing_fee_rate' => 2, 'processing_fee_flat' => 0,
        'penalty_rate_daily' => 0.1, 'penalty_grace_days' => 0, 'status' => 'active',
    ]);
    foreach ([
        'principal_receivable' => '1110', 'interest_receivable' => '1120',
        'interest_income' => '4003', 'fee_income' => '4004',
        'penalty_receivable' => '1130', 'penalty_income' => '4004',
    ] as $op => $code) {
        $product->mappings()->create([
            'operation_type' => $op,
            'account_id' => Account::where('ledger_id', $master->id)->where('code', $code)->value('id'),
        ]);
    }

    $custLedger = $customers;
    $custAccount = Account::create([
        'name' => 'Adaeze Okafor', 'code' => 'LC-2026-00001', 'description' => 'Test',
        'ledger_id' => $custLedger->id, 'balance_type' => 'debit',
        'category' => 'AccountsReceivable', 'status' => 'active',
    ]);
    $customer = LoanCustomer::create([
        'customer_no' => 'LC-2026-00001', 'company_id' => $company->id, 'account_id' => $custAccount->id,
        'customer_type' => 'individual', 'first_name' => 'Adaeze', 'last_name' => 'Okafor',
        'phone' => '08034567891', 'address' => '14 Allen Ave', 'bvn' => '22145678901', 'nin' => '90123456781',
        'means_of_id_type' => 'drivers_license', 'means_of_id_number' => 'ABC1',
        'proof_of_address_type' => 'utility_bill', 'kyc_tier' => 'tier3', 'kyc_status' => 'verified',
        'status' => 'active',
    ]);

    return compact('company', 'master', 'customers', 'bank', 'product', 'customer', 'custAccount', 'interestRec', 'penaltyRec', 'interestInc', 'feeInc');
}

test('flat schedule math splits principal and interest evenly', function () {
    $gen = app(ScheduleGenerator::class);
    $rows = $gen->generate('120000', '36', InterestMethod::Flat, RepaymentFrequency::Monthly, 12, now()->addMonth()->toDateString(), '2400');

    expect($rows)->toHaveCount(12);
    $principalSum = collect($rows)->sum(fn ($r) => (float) $r['principal_due']);
    $interestSum = collect($rows)->sum(fn ($r) => (float) $r['interest_due']);
    expect($principalSum)->toBe(120000.0);
    expect($interestSum)->toBe(43200.0);
    expect((float) $rows[0]['fee_due'])->toBe(2400.0);
});

test('full loan flow posts journals and repays to close', function () {
    ['bank' => $bank, 'product' => $product, 'customer' => $customer, 'custAccount' => $custAccount, 'feeInc' => $feeInc] = seedLifecycleCompany();

    $firstDue = now()->addMonth()->toDateString();
    $this->post(route('loans.loans.store'), [
        'loan_product_id' => $product->id,
        'loan_customer_id' => $customer->id,
        'principal' => 120000,
        'tenor' => 3,
        'first_due_date' => $firstDue,
        'notes' => 'test loan',
    ])->assertRedirect();

    $loan = Loan::firstOrFail();
    expect($loan->status)->toBe('pending_approval');
    expect($loan->schedules()->count())->toBe(3);
    // flat 36% annual monthly = 3% per period: 120000*0.03*3 = 10800
    expect((float) $loan->total_interest)->toBe(10800.0);

    $this->post(route('loans.loans.approve', ['loan' => $loan->id]))->assertRedirect();
    expect($loan->fresh()->status)->toBe('approved');

    $this->post(route('loans.loans.disburse', ['loan' => $loan->id]), [
        'bank_account_id' => (string) $bank->id,
        'disbursement_date' => now()->toDateString(),
    ])->assertRedirect();

    $loan = $loan->fresh();
    expect($loan->status)->toBe('active');
    $txn = JournalTransaction::findOrFail($loan->disbursement_journal_id);
    expect($txn->status)->toBe('POSTED');
    expect($txn->posted_at)->not->toBeNull();
    $debits = $txn->entries()->where('type', 'debit')->sum('value');
    $credits = $txn->entries()->where('type', 'credit')->sum('value');
    expect((float) $debits)->toBe((float) $credits);
    // Dr bank P (120000), Cr customer net of fee (117600), Cr fee income (2400)
    expect((float) $debits)->toBe(120000.0);
    expect((float) $txn->entries()->where('account_id', $custAccount->id)->where('type', 'credit')->sum('value'))->toBe(117600.0);
    expect((float) $txn->entries()->where('account_id', $feeInc->id)->where('type', 'credit')->sum('value'))->toBe(2400.0);

    // Accrue some interest after disbursement (idempotent)
    $accrualDate = now()->addDays(5)->toDateString();
    $this->artisan('loans:accrue-interest', ['--date' => $accrualDate])->assertOk();
    $this->artisan('loans:accrue-interest', ['--date' => $accrualDate])->assertOk();

    $accruals = JournalTransaction::where('description', 'like', 'Interest accrual%')->get();
    expect($accruals)->not->toBeEmpty();
    expect($accruals->every(fn (JournalTransaction $entry) => $entry->status === 'POSTED'))->toBeTrue();

    // Repay everything: total repayable 120000+10800+2400 = 133200
    $this->post(route('loans.loans.repay', ['loan' => $loan->id]), [
        'amount' => 133200,
        'payment_account_id' => (string) $bank->id,
        'payment_date' => now()->toDateString(),
    ])->assertRedirect();

    $loan = $loan->fresh();
    expect($loan->status)->toBe('closed');
    $repayment = $loan->repayments()->firstOrFail();
    expect((float) $repayment->amount)->toBe(133200.0);
    expect($repayment->journal_transaction_id)->not->toBeNull();
    $repayTxn = JournalTransaction::findOrFail($repayment->journal_transaction_id);
    expect($repayTxn->status)->toBe('POSTED');
    expect($repayTxn->posted_at)->not->toBeNull();
});

test('loan index lists joined product and customer data', function () {
    ['product' => $product, 'customer' => $customer] = seedLifecycleCompany();

    $this->post(route('loans.loans.store'), [
        'loan_product_id' => $product->id,
        'loan_customer_id' => $customer->id,
        'principal' => 50000,
        'tenor' => 2,
        'first_due_date' => now()->addMonth()->toDateString(),
    ])->assertRedirect();

    $loan = Loan::firstOrFail();
    $response = $this->get(route('loans.loans.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Loan/Loans/Index')
        ->has('loans', 1)
        ->where('loans.0.loan_no', $loan->loan_no)
        ->where('loans.0.customer_no', $customer->customer_no)
        ->where('loans.0.product_code', $product->code)
    );
});

test('penalty accrues only on overdue and posts journals', function () {
    ['bank' => $bank, 'product' => $product, 'customer' => $customer] = seedLifecycleCompany();

    $this->post(route('loans.loans.store'), [
        'loan_product_id' => $product->id,
        'loan_customer_id' => $customer->id,
        'principal' => 100000,
        'tenor' => 1,
        'first_due_date' => now()->addMonth()->toDateString(),
    ])->assertRedirect();
    $loan = Loan::firstOrFail();
    $this->post(route('loans.loans.approve', ['loan' => $loan->id]));
    $this->post(route('loans.loans.disburse', ['loan' => $loan->id]), [
        'bank_account_id' => (string) $bank->id,
        'disbursement_date' => now()->toDateString(),
    ]);

    $loan = $loan->fresh();
    $schedule = $loan->schedules()->firstOrFail();
    $lateDate = Carbon::parse($schedule->due_date)->addDays(10)->toDateString();

    $this->artisan('loans:apply-penalties', ['--date' => $lateDate])->assertOk();
    $penaltyAfterFirst = (float) $loan->fresh()->schedules()->firstOrFail()->penalty_due;
    expect($penaltyAfterFirst)->toBeGreaterThan(0);

    $penalties = JournalTransaction::where('description', 'like', 'Penalty%')->get();
    expect($penalties)->not->toBeEmpty();
    expect($penalties->every(fn (JournalTransaction $entry) => $entry->status === 'POSTED'))->toBeTrue();

    $this->artisan('loans:apply-penalties', ['--date' => $lateDate])->assertOk();
    $penaltyAfterSecond = (float) $loan->fresh()->schedules()->firstOrFail()->penalty_due;
    expect($penaltyAfterSecond)->toBe($penaltyAfterFirst);
});
