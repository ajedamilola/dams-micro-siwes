<?php

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
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
        JournalTransactionSeeder::class,
    ]);
});

test('seeded entries post to leaf accounts only', function () {
    $accounts = Account::all()->keyBy('id');

    expect(JournalEntry::count())->toBeGreaterThan(0);

    foreach (JournalEntry::all() as $entry) {
        $account = $accounts->get($entry->account_id);

        expect($account)->not->toBeNull("Entry {$entry->id} references a missing account.");
        expect(
            $accounts->contains(fn (Account $row) => $row->parent_id === $account->id)
        )->toBeFalse("Entry {$entry->id} posts to group account {$account->code}.");
        expect($account->subledger_id)->toBeNull("Entry {$entry->id} posts to control account {$account->code}.");
    }
});

test('seeded transactions balance debits to credits', function () {
    expect(JournalTransaction::count())->toBeGreaterThan(0);

    foreach (JournalTransaction::all() as $transaction) {
        $entries = JournalEntry::where('journal_transaction_id', $transaction->id)->get();
        $debits = $entries->where('type', 'debit')->sum('value');
        $credits = $entries->where('type', 'credit')->sum('value');

        expect((float) $debits)->toEqual((float) $credits, "Transaction {$transaction->ref} is out of balance.");
    }
});
