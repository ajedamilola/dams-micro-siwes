<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use App\Models\SystemSettings;
use App\Support\BusinessDate;

function setBusinessDate(string $date): void
{
    SystemSettings::query()->updateOrCreate(
        ['name' => 'business_date'],
        ['value' => $date],
    );
}

function seedSnapshotLedger(): array
{
    $company = Company::create(['name' => 'Snapshot Company', 'code' => 'SNP']);
    $ledger = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);

    $cash = Account::create([
        'name' => 'Cash on Hand', 'code' => '1001', 'description' => 'Cash',
        'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'active',
    ]);
    $income = Account::create([
        'name' => 'Sales', 'code' => '4001', 'description' => 'Sales',
        'ledger_id' => $ledger->id, 'balance_type' => 'credit',
        'category' => 'OperatingRevenue', 'status' => 'active',
    ]);

    return compact('company', 'ledger', 'cash', 'income');
}

test('business date cannot move while journals are unposted', function () {
    setBusinessDate('2026-10-15');

    JournalTransaction::create([
        'description' => 'Awaiting review',
        'ref' => 'TXN-9001',
        'status' => 'SUBMITTED',
        'effective_date' => '2026-10-15',
    ]);

    $this->post(route('general-ledger.business-date.advance'), ['date' => '2026-10-16'])
        ->assertSessionHasErrors('date');

    expect(BusinessDate::today())->toBe('2026-10-15');
});

test('business date advances once every journal is posted', function () {
    setBusinessDate('2026-10-15');

    JournalTransaction::create([
        'description' => 'Posted journal',
        'ref' => 'TXN-9002',
        'status' => 'POSTED',
        'effective_date' => '2026-10-15',
        'posted_at' => now(),
    ]);

    $this->post(route('general-ledger.business-date.advance'), ['date' => '2026-10-16'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(BusinessDate::today())->toBe('2026-10-16');
});

test('advancing rolls closing balances into the next opening balances', function () {
    setBusinessDate('2026-10-15');
    ['cash' => $cash, 'income' => $income] = seedSnapshotLedger();

    AccountOpeningBalance::create([
        'date' => '2026-10-01',
        'account_id' => $cash->id,
        'balance' => '200.0000',
    ]);

    $transaction = JournalTransaction::create([
        'description' => 'Closing day sale',
        'ref' => 'TXN-9003',
        'status' => 'POSTED',
        'effective_date' => '2026-10-15',
        'posted_at' => now(),
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 50, 'balance_after' => 0,
        'journal_transaction_id' => $transaction->id, 'account_id' => $cash->id,
    ]);
    JournalEntry::create([
        'type' => 'credit', 'value' => 50, 'balance_after' => 0,
        'journal_transaction_id' => $transaction->id, 'account_id' => $income->id,
    ]);

    $this->post(route('general-ledger.business-date.advance'), ['date' => '2026-10-16'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $openings = AccountOpeningBalance::query()->where('date', '2026-10-16')->get()->keyBy('account_id');

    expect((float) $openings->get($cash->id)->balance)->toBe(250.0)
        ->and((float) $openings->get($income->id)->balance)->toBe(50.0)
        ->and(BusinessDate::today())->toBe('2026-10-16');
});

test('journal effective date cannot be later than the business date', function () {
    setBusinessDate('2026-10-15');

    $this->post(route('general-ledger.transactions.store'), [
        'description' => 'Future dated journal',
        'effective_date' => '2026-10-16',
    ])->assertSessionHasErrors('effective_date');

    expect(JournalTransaction::query()->count())->toBe(0);
});

test('loan first due date must be after the business date', function () {
    setBusinessDate('2026-10-15');

    $this->post(route('loans.loans.store'), [
        'loan_product_id' => 'missing',
        'loan_customer_id' => 'missing',
        'principal' => '1000',
        'tenor' => 3,
        'first_due_date' => '2026-10-15',
    ])->assertSessionHasErrors('first_due_date');
});
