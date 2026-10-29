<?php

use App\Models\GeneralLedger\Account;
use App\Models\SystemSettings;
use Database\Seeders\BalanceSheetLayoutSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\CustomerAccountSeeder;
use Database\Seeders\JournalTransactionSeeder;
use Database\Seeders\LedgerSeeder;
use Database\Seeders\SystemSettingsSeeder;

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        CompanySeeder::class,
        LedgerSeeder::class,
        ChartOfAccountsSeeder::class,
        CustomerAccountSeeder::class,
        BalanceSheetLayoutSeeder::class,
        JournalTransactionSeeder::class,
    ]);
});

test('settings page shows headings with resolved accounts', function () {
    $response = $this->get(route('general-ledger.settings.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('GeneralLedger/Settings/Index')
        ->has('sections', 5)
        ->has('accounts')
        ->has('unmapped_accounts')
    );

    $sections = $response->viewData('page')['props']['sections'];
    $labels = [];
    foreach ($sections as $section) {
        foreach ($section['headings'] as $heading) {
            $labels[] = $heading['label'];
        }
    }
    expect($labels)->toContain('Trade Payables', 'Cash and Bank');
});

test('settings update repoints headings and the balance sheet follows', function () {
    $tradePayables = Account::query()->where('code', '2001')->firstOrFail();
    $otherPayables = Account::query()->where('code', '2002')->firstOrFail();

    $sections = SystemSettings::query()->where('name', 'balance_sheet_layout')->firstOrFail()->value['sections'];
    foreach ($sections as &$section) {
        foreach ($section['headings'] as &$heading) {
            if ($heading['label'] === 'Trade Payables') {
                $heading['account_ids'] = [$otherPayables->id];
            }
            if ($heading['label'] === 'Other Payables') {
                $heading['account_ids'] = [];
            }
        }
        unset($heading);
    }
    unset($section);

    $this->put(route('general-ledger.settings.update'), ['sections' => $sections])
        ->assertRedirect();

    $statement = $this->get(route('general-ledger.balance-sheet'))
        ->viewData('page')['props']['statement'];

    $totals = [];
    foreach ($statement['liabilities_current'] as $group) {
        $totals[$group['label']] = $group['total'];
    }
    // Nothing ever posted to 2002, so the repointed heading reads zero.
    expect(bccomp($totals['Trade Payables'], '0', 4))->toBe(0);
});

test('settings update rejects unknown accounts', function () {
    $sections = SystemSettings::query()->where('name', 'balance_sheet_layout')->firstOrFail()->value['sections'];
    $sections[0]['headings'][0]['account_ids'] = ['00000000-0000-0000-0000-000000000000'];

    $this->put(route('general-ledger.settings.update'), ['sections' => $sections])
        ->assertInvalid(['sections.0.headings.0.account_ids.0']);
});

test('settings update rejects a missing heading label', function () {
    $sections = SystemSettings::query()->where('name', 'balance_sheet_layout')->firstOrFail()->value['sections'];
    $sections[0]['headings'][0]['label'] = '';

    $this->put(route('general-ledger.settings.update'), ['sections' => $sections])
        ->assertInvalid(['sections.0.headings.0.label']);
});
