<?php

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;

function seedSearchChart(): array
{
    $company = Company::create(['name' => 'Test Company', 'code' => 'TST']);
    $ledger = Ledger::create(['name' => 'Master Chart', 'code' => 'MC', 'company_id' => $company->id]);

    $group = Account::create([
        'name' => 'Cash and Bank', 'code' => '1100', 'description' => 'Group',
        'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'active',
    ]);

    for ($i = 1; $i <= 12; $i++) {
        Account::create([
            'name' => "Cash Account {$i}", 'code' => sprintf('11%02d', $i), 'description' => 'Leaf',
            'parent_id' => $group->id, 'ledger_id' => $ledger->id, 'balance_type' => 'debit',
            'category' => 'CashAndCashEquivalents', 'status' => 'active',
        ]);
    }

    Account::create([
        'name' => 'Dormant Account', 'code' => '1199', 'description' => 'Inactive',
        'parent_id' => $group->id, 'ledger_id' => $ledger->id, 'balance_type' => 'debit',
        'category' => 'CashAndCashEquivalents', 'status' => 'inactive',
    ]);

    return compact('ledger', 'group');
}

test('account search returns at most 10 leaf accounts', function () {
    seedSearchChart();

    $response = $this->getJson(route('general-ledger.accounts.search'));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(10);
    // Group and inactive accounts are not postable, so they are excluded.
    expect(collect($data)->pluck('code'))->not->toContain('1100', '1199');
});

test('account search filters by keyword on code or name', function () {
    seedSearchChart();

    $byCode = $this->getJson(route('general-ledger.accounts.search', ['q' => '1101']))->json('data');
    expect($byCode)->toHaveCount(1);
    expect($byCode[0]['code'])->toBe('1101');

    $byName = $this->getJson(route('general-ledger.accounts.search', ['q' => 'cash account 2']))->json('data');
    expect($byName)->not->toBeEmpty();
    expect($byName[0]['name'])->toContain('Cash Account 2');
});
