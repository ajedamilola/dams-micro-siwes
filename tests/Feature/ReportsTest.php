<?php

use Database\Seeders\BalanceSheetLayoutSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\JournalTransactionSeeder;
use Database\Seeders\LedgerSeeder;
use Database\Seeders\SystemSettingsSeeder;

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        CompanySeeder::class,
        LedgerSeeder::class,
        ChartOfAccountsSeeder::class,
        BalanceSheetLayoutSeeder::class,
        JournalTransactionSeeder::class,
    ]);
});

test('trial balance uses real posted balances and balances', function () {
    $response = $this->get(route('general-ledger.trial-balance'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Reports/TrialBalance')
        ->has('statement.rows')
        ->where('statement.company.code', 'DEMO')
    );

    $statement = $response->viewData('page')['props']['statement'];
    expect(count($statement['rows']))->toBeGreaterThan(0);
    expect(bccomp($statement['debit_total'], $statement['credit_total'], 4))->toBe(0);
    expect((float) $statement['debit_total'])->toBeGreaterThan(0);

    // Every row references a real account in its own ledger.
    $codes = array_column($statement['rows'], 'code');
    expect($codes)->toContain('1002', '2001');
    foreach ($statement['rows'] as $row) {
        expect($row['account_id'])->not->toBeEmpty();
        expect($row['account_ledger_id'])->not->toBeEmpty();
    }
});

test('trial balance as at an earlier date excludes later postings', function () {
    $asAt = now()->subDay()->toDateString();

    $response = $this->get(route('general-ledger.trial-balance', ['as_at' => $asAt]));

    $response->assertOk();
    $statement = $response->viewData('page')['props']['statement'];

    // Only the backdated owner contribution (TXN-0001) falls inside.
    expect(bccomp($statement['debit_total'], '500000.0000', 4))->toBe(0);
    expect(bccomp($statement['credit_total'], '500000.0000', 4))->toBe(0);
});

test('balance sheet balances assets against liabilities and equity', function () {
    $response = $this->get(route('general-ledger.balance-sheet'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Reports/BalanceSheet')
        ->where('statement.company.code', 'DEMO')
        ->has('statement.assets_current')
        ->has('statement.equity')
        ->has('statement.surplus')
    );

    $statement = $response->viewData('page')['props']['statement'];
    $right = bcadd($statement['liabilities_total'], $statement['equity_total'], 4);
    expect(bccomp($statement['assets_total'], $right, 4))->toBe(0);
});

test('balance sheet follows the configured headings', function () {
    $response = $this->get(route('general-ledger.balance-sheet'));

    $statement = $response->viewData('page')['props']['statement'];

    $groupByLabel = function (array $groups): array {
        $map = [];
        foreach ($groups as $group) {
            $map[$group['label']] = $group;
        }

        return $map;
    };

    $currentLiabilities = $groupByLabel($statement['liabilities_current']);
    expect(bccomp($currentLiabilities['Trade Payables']['total'], '370000.0000', 4))->toBe(0);

    $currentAssets = $groupByLabel($statement['assets_current']);
    // Customer activity is no longer seeded as raw GL credits.
    expect(bccomp($currentAssets['Trade Receivables']['total'], '0', 4))->toBe(0);
    expect(bccomp($currentAssets['Cash and Bank']['total'], '740000.0000', 4))->toBe(0);
});
