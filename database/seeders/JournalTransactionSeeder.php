<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use App\Models\SystemSettings;
use App\Services\GeneralLedger\RebuildLedgerBalances;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class JournalTransactionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Posted operating transactions on the Master Chart. Customer account
     * activity is not seeded raw here: customer balances come from the
     * loans seeded by LoanSeeder, which post through the loan lifecycle.
     * Each transaction is dated `days_ago` before the business date, so
     * activity on earlier days is seeded as backdated entries. Entries
     * reference accounts by code and post to leaf accounts only.
     *
     * @var array<int, array{ref: string, description: string, days_ago: int, entries: array<int, array{code: string, type: string, value: float}>}>
     */
    private const TRANSACTIONS = [
        [
            'ref' => 'TXN-0001',
            'description' => 'Owner capital contribution',
            'days_ago' => 1,
            'entries' => [
                ['code' => '1002', 'type' => 'debit', 'value' => 500000.00],
                ['code' => '3001', 'type' => 'credit', 'value' => 500000.00],
            ],
        ],
        [
            'ref' => 'TXN-0002',
            'description' => 'Equipment purchased on credit',
            'days_ago' => 0,
            'entries' => [
                ['code' => '1502', 'type' => 'debit', 'value' => 250000.00],
                ['code' => '2001', 'type' => 'credit', 'value' => 250000.00],
            ],
        ],
        [
            'ref' => 'TXN-0003',
            'description' => 'Bank loan drawdown',
            'days_ago' => 0,
            'entries' => [
                ['code' => '1002', 'type' => 'debit', 'value' => 300000.00],
                ['code' => '2501', 'type' => 'credit', 'value' => 300000.00],
            ],
        ],
        [
            'ref' => 'TXN-0004',
            'description' => 'Inventory purchased on credit',
            'days_ago' => 0,
            'entries' => [
                ['code' => '1201', 'type' => 'debit', 'value' => 120000.00],
                ['code' => '2001', 'type' => 'credit', 'value' => 120000.00],
            ],
        ],
        [
            'ref' => 'TXN-0010',
            'description' => 'Monthly salaries paid',
            'days_ago' => 0,
            'entries' => [
                ['code' => '5101', 'type' => 'debit', 'value' => 60000.00],
                ['code' => '1002', 'type' => 'credit', 'value' => 60000.00],
            ],
        ],
        [
            'ref' => 'TXN-0011',
            'description' => 'Cost of goods sold',
            'days_ago' => 0,
            'entries' => [
                ['code' => '5001', 'type' => 'debit', 'value' => 80000.00],
                ['code' => '1202', 'type' => 'credit', 'value' => 80000.00],
            ],
        ],
        [
            'ref' => 'TXN-0012',
            'description' => 'Depreciation for the period',
            'days_ago' => 0,
            'entries' => [
                ['code' => '5201', 'type' => 'debit', 'value' => 20000.00],
                ['code' => '1504', 'type' => 'credit', 'value' => 20000.00],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'DEMO')->firstOrFail();

        $businessDate = Carbon::parse(
            SystemSettings::query()->where('name', 'business_date')->firstOrFail()->value,
        );

        $ledgerIds = Ledger::query()
            ->where('company_id', $company->id)
            ->pluck('id');

        $accounts = Account::query()
            ->whereIn('ledger_id', $ledgerIds)
            ->get()
            ->keyBy('code');

        // Remove customer GL entries produced by older seed runs. Customer
        // balances now come only from seeded loans.
        $legacyRefs = ['TXN-0005', 'TXN-0006', 'TXN-0007', 'TXN-0008', 'TXN-0009'];
        $legacyIds = JournalTransaction::query()->whereIn('ref', $legacyRefs)->pluck('id');
        if ($legacyIds->isNotEmpty()) {
            JournalEntry::query()->whereIn('journal_transaction_id', $legacyIds)->delete();
            JournalTransaction::query()->whereIn('id', $legacyIds)->delete();
        }

        foreach (self::TRANSACTIONS as $transactionData) {
            $attributes = [
                'description' => $transactionData['description'],
                'status' => 'POSTED',
                'effective_date' => $businessDate->copy()->subDays($transactionData['days_ago']),
            ];

            $transaction = JournalTransaction::query()->firstOrCreate(
                ['ref' => $transactionData['ref']],
                $attributes + ['posted_at' => now()],
            );

            if (! $transaction->wasRecentlyCreated) {
                // Keep the seeded transaction in step with the definition on re-runs.
                $transaction->update($attributes);

                continue;
            }

            foreach ($transactionData['entries'] as $entry) {
                $account = $accounts->get($entry['code'])
                    ?? throw new RuntimeException("Missing account [{$entry['code']}]. Seed the chart of accounts first.");

                $this->ensurePostable($accounts, $account);

                JournalEntry::query()->create([
                    'type' => $entry['type'],
                    'value' => $entry['value'],
                    'balance_after' => 0,
                    'journal_transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                ]);
            }
        }

        app(RebuildLedgerBalances::class)->run($businessDate);
    }

    /**
     * Journal entries post to leaf accounts only. Group accounts and
     * control accounts are totals whose balances roll up from children.
     *
     * @param  Collection<string, Account>  $accounts
     */
    private function ensurePostable(Collection $accounts, Account $account): void
    {
        $isGroup = $accounts->contains(fn (Account $row) => $row->parent_id === $account->id);

        if ($isGroup || $account->subledger_id !== null) {
            throw new RuntimeException("Cannot post to [{$account->code} {$account->name}]. Post to a leaf account instead.");
        }
    }
}
