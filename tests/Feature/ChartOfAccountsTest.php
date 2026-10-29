<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;

function seedChart(): array
{
    $company = Company::create(['name' => 'Test Company', 'code' => 'TST']);
    $ledger = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);
    $otherLedger = Ledger::create(['name' => 'Customers', 'code' => 'CUST', 'company_id' => $company->id]);

    $group = Account::create([
        'name' => 'Cash and Bank',
        'code' => '1100',
        'description' => 'Cash holdings',
        'ledger_id' => $ledger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
        'status' => 'active',
    ]);
    $leaf = Account::create([
        'name' => 'Main Operating Account',
        'code' => '1110',
        'description' => 'Daily operations',
        'parent_id' => $group->id,
        'ledger_id' => $ledger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
        'status' => 'active',
    ]);

    return compact('company', 'ledger', 'otherLedger', 'group', 'leaf');
}

test('chart of accounts page renders real accounts with ledgers', function () {
    ['ledger' => $ledger, 'group' => $group, 'leaf' => $leaf] = seedChart();

    $response = $this->get(route('general-ledger.accounts.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Accounts/Index')
        ->has('accounts', 2)
        ->has('ledgers', 2)
        ->where('accounts.0.code', '1100')
        ->where('accounts.0.is_group', true)
        ->where('accounts.0.children_count', 1)
        ->where('accounts.0.type', 'Asset')
        ->where('accounts.1.level', 1)
    );
});

test('account detail page renders with ancestors and children', function () {
    ['ledger' => $ledger, 'leaf' => $leaf] = seedChart();

    $response = $this->get(route('general-ledger.accounts.show', [
        'ledger' => $ledger->id,
        'account' => $leaf->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Accounts/Show')
        ->where('account.code', '1110')
        ->where('account.name', 'Main Operating Account')
        ->has('ancestors', 1)
        ->has('children', 0)
    );
});

test('account from another ledger returns a 404', function () {
    ['ledger' => $ledger, 'otherLedger' => $otherLedger, 'leaf' => $leaf] = seedChart();

    $this->get(route('general-ledger.accounts.show', [
        'ledger' => $otherLedger->id,
        'account' => $leaf->id,
    ]))->assertNotFound();
});

test('store creates an account and redirects to its page', function () {
    ['ledger' => $ledger] = seedChart();

    $response = $this->post(route('general-ledger.accounts.store'), [
        'name' => 'Petty Cash',
        'code' => '1130',
        'ledger_id' => $ledger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
    ]);

    $account = Account::where('ledger_id', $ledger->id)->where('code', '1130')->first();
    expect($account)->not->toBeNull();
    expect($account->status)->toBe('active');
    $response->assertRedirect(route('general-ledger.accounts.show', [
        'ledger' => $ledger->id,
        'account' => $account->id,
    ]));
});

test('store inherits category and balance from the parent', function () {
    ['ledger' => $ledger, 'group' => $group] = seedChart();

    $this->post(route('general-ledger.accounts.store'), [
        'name' => 'Payroll Account',
        'code' => '1120',
        'ledger_id' => $ledger->id,
        'parent_id' => $group->id,
    ])->assertRedirect();

    $account = Account::where('code', '1120')->first();
    expect($account->category)->toBe('CashAndCashEquivalents');
    expect($account->balance_type)->toBe('debit');
});

test('store rejects a duplicate code within the same ledger', function () {
    ['ledger' => $ledger, 'otherLedger' => $otherLedger] = seedChart();

    // Same code in another ledger is fine.
    $this->post(route('general-ledger.accounts.store'), [
        'name' => 'Cash and Bank Copy',
        'code' => '1100',
        'ledger_id' => $otherLedger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
    ])->assertRedirect();

    // Same code in the same ledger is rejected with a field error.
    $this->post(route('general-ledger.accounts.store'), [
        'name' => 'Duplicate',
        'code' => '1100',
        'ledger_id' => $ledger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
    ])->assertInvalid(['code']);
});

test('update edits an account', function () {
    ['ledger' => $ledger, 'leaf' => $leaf] = seedChart();

    $this->put(route('general-ledger.accounts.update', [
        'ledger' => $ledger->id,
        'account' => $leaf->id,
    ]), [
        'name' => 'Main Operating Account Renamed',
        'status' => 'inactive',
    ])->assertRedirect();

    expect($leaf->fresh()->name)->toBe('Main Operating Account Renamed');
    expect($leaf->fresh()->status)->toBe('inactive');
});

test('update rejects a parent cycle', function () {
    ['ledger' => $ledger, 'group' => $group, 'leaf' => $leaf] = seedChart();

    $this->put(route('general-ledger.accounts.update', [
        'ledger' => $ledger->id,
        'account' => $group->id,
    ]), ['parent_id' => $leaf->id])->assertInvalid(['parent_id']);
});

test('account balance comes from posted entries and rolls up children', function () {
    ['ledger' => $ledger, 'group' => $group, 'leaf' => $leaf] = seedChart();

    $sibling = Account::create([
        'name' => 'Petty Cash',
        'code' => '1120',
        'description' => 'Office float',
        'parent_id' => $group->id,
        'ledger_id' => $ledger->id,
        'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents',
        'status' => 'active',
    ]);

    $posted = JournalTransaction::create([
        'description' => 'Seed postings',
        'ref' => 'TXN-TEST-1',
        'status' => 'POSTED',
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 5000, 'balance_after' => 5000,
        'journal_transaction_id' => $posted->id, 'account_id' => $leaf->id,
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 1500, 'balance_after' => 1500,
        'journal_transaction_id' => $posted->id, 'account_id' => $sibling->id,
    ]);

    $draft = JournalTransaction::create([
        'description' => 'Unposted',
        'ref' => 'TXN-TEST-2',
        'status' => 'DRAFT',
    ]);
    JournalEntry::create([
        'type' => 'debit', 'value' => 99999, 'balance_after' => 99999,
        'journal_transaction_id' => $draft->id, 'account_id' => $leaf->id,
    ]);

    // Leaf ignores the draft entry; group sums its own (zero) plus children.
    expect((string) $leaf->fresh()->account_balance)->toBe('5000');
    expect((string) $group->fresh()->account_balance)->toBe('6500.0000');

    $response = $this->get(route('general-ledger.accounts.index'));
    $accounts = collect($response->viewData('page')['props']['accounts'])->keyBy('code');
    expect($accounts['1110']['account_balance'])->toBe('5000');
    expect($accounts['1100']['account_balance'])->toBe('6500.0000');
});

test('destroy deletes a leaf account without entries', function () {
    ['ledger' => $ledger, 'leaf' => $leaf] = seedChart();

    $this->delete(route('general-ledger.accounts.destroy', [
        'ledger' => $ledger->id,
        'account' => $leaf->id,
    ]))->assertRedirect(route('general-ledger.accounts.index'));

    expect(Account::find($leaf->id))->toBeNull();
});

test('destroy refuses an account with sub-accounts or entries', function () {
    ['ledger' => $ledger, 'group' => $group, 'leaf' => $leaf] = seedChart();

    $this->delete(route('general-ledger.accounts.destroy', [
        'ledger' => $ledger->id,
        'account' => $group->id,
    ]))->assertRedirect();
    expect(Account::find($group->id))->not->toBeNull();

    JournalEntry::create([
        'type' => 'debit',
        'value' => 1000,
        'balance_after' => 1000,
        'journal_transaction_id' => 1,
        'account_id' => $leaf->id,
    ]);

    $this->delete(route('general-ledger.accounts.destroy', [
        'ledger' => $ledger->id,
        'account' => $leaf->id,
    ]))->assertRedirect();
    expect(Account::find($leaf->id))->not->toBeNull();
});
