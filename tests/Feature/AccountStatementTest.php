<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use App\Models\SystemSettings;
use Inertia\Testing\AssertableInertia;

function seedStatementAccount(): array
{
    $company = Company::create(['name' => 'Statement Co', 'code' => 'STC']);
    $ledger = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);

    $account = Account::create([
        'name' => 'Cash on Hand', 'code' => '1001', 'description' => 'Cash',
        'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'active',
    ]);

    $group = Account::create([
        'name' => 'Current Assets', 'code' => '1000', 'description' => 'Group',
        'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'active',
    ]);

    $account->update(['parent_id' => $group->id]);

    return compact('company', 'ledger', 'account', 'group');
}

function postEntry(Account $account, string $date, string $type, float $value, string $ref): void
{
    $transaction = JournalTransaction::create([
        'description' => "Entry {$ref}",
        'ref' => $ref,
        'status' => 'POSTED',
        'effective_date' => $date,
        'posted_at' => now(),
    ]);

    JournalEntry::create([
        'type' => $type,
        'value' => $value,
        'balance_after' => 0,
        'journal_transaction_id' => $transaction->id,
        'account_id' => $account->id,
    ]);
}

test('leaf account statement shows opening balance and every movement', function () {
    SystemSettings::query()->create(['name' => 'business_date', 'value' => '2026-10-20']);
    ['account' => $account] = seedStatementAccount();

    AccountOpeningBalance::create([
        'date' => '2026-10-01',
        'account_id' => $account->id,
        'balance' => '100.0000',
    ]);

    postEntry($account, '2026-10-05', 'debit', 20, 'TXN-PRE');
    for ($i = 1; $i <= 20; $i++) {
        postEntry($account, '2026-10-12', 'debit', 1, "TXN-{$i}");
    }

    $this->get(route('general-ledger.accounts.show', [
        'ledger' => $account->ledger_id,
        'account' => $account->id,
        'from' => '2026-10-10',
        'to' => '2026-10-20',
    ]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('GeneralLedger/Accounts/Show')
        ->where('is_leaf', true)
        ->where('statement.opening_balance', 120)
        ->where('statement.closing_balance', 140)
        ->where('statement.debits', 20)
        ->has('statement.rows', 20)
        ->where('statement_filters.from', '2026-10-10')
        ->where('statement_filters.to', '2026-10-20')
    );
});

test('group accounts do not return a statement', function () {
    ['group' => $group] = seedStatementAccount();

    $this->get(route('general-ledger.accounts.show', [
        'ledger' => $group->ledger_id,
        'account' => $group->id,
    ]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('is_leaf', false)
        ->where('statement', null)
    );
});

test('account statement exports as an xlsx download', function () {
    SystemSettings::query()->create(['name' => 'business_date', 'value' => '2026-10-20']);
    ['account' => $account] = seedStatementAccount();
    postEntry($account, '2026-10-12', 'debit', 25, 'TXN-EXP');

    $this->get(route('general-ledger.accounts.statement.export', [
        'ledger' => $account->ledger_id,
        'account' => $account->id,
        'from' => '2026-10-10',
        'to' => '2026-10-20',
    ]))
        ->assertOk()
        ->assertDownload('statement-1001-2026-10-10-to-2026-10-20.xlsx');
});
