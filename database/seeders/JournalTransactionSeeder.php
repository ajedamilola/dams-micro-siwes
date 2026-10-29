<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use App\Models\GeneralLedger\Ledger;
use App\Models\SystemSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class JournalTransactionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Posted transactions spread across the Master Chart and the Customers subledger.
     * Each transaction is dated `days_ago` before the business date, so activity on
     * earlier days is seeded as backdated entries. Entries reference accounts by code
     * and post to leaf accounts only. Group and control accounts are totals; their
     * balances roll up from children.
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
            'ref' => 'TXN-0005',
            'description' => 'Credit sale to Adaeze Okafor',
            'days_ago' => 1,
            'entries' => [
                ['code' => 'CUST-001', 'type' => 'debit', 'value' => 40000.00],
                ['code' => '4001', 'type' => 'credit', 'value' => 40000.00],
            ],
        ],
        [
            'ref' => 'TXN-0006',
            'description' => 'Credit sale to Tunde Bakare',
            'days_ago' => 1,
            'entries' => [
                ['code' => 'CUST-002', 'type' => 'debit', 'value' => 35000.00],
                ['code' => '4001', 'type' => 'credit', 'value' => 35000.00],
            ],
        ],
        [
            'ref' => 'TXN-0007',
            'description' => 'Credit sale to Chiamaka Eze',
            'days_ago' => 1,
            'entries' => [
                ['code' => 'CUST-003', 'type' => 'debit', 'value' => 25000.00],
                ['code' => '4001', 'type' => 'credit', 'value' => 25000.00],
            ],
        ],
        [
            'ref' => 'TXN-0008',
            'description' => 'Payment received from Adaeze Okafor',
            'days_ago' => 0,
            'entries' => [
                ['code' => '1002', 'type' => 'debit', 'value' => 15000.00],
                ['code' => 'CUST-001', 'type' => 'credit', 'value' => 15000.00],
            ],
        ],
        [
            'ref' => 'TXN-0009',
            'description' => 'Credit note issued to Tunde Bakare',
            'days_ago' => 0,
            'entries' => [
                ['code' => '4001', 'type' => 'debit', 'value' => 10000.00],
                ['code' => 'CUST-002', 'type' => 'credit', 'value' => 10000.00],
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
                    ?? throw new RuntimeException("Missing account [{$entry['code']}]. Seed the chart of accounts and customers first.");

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

        $this->refreshBalances($accounts);
        $this->seedOpeningBalances($accounts, $businessDate);
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

    /**
     * Recalculate balances from posted entries in effective-date order (so
     * backdated entries form a correct running balance), then roll subledger
     * totals up to their control accounts.
     *
     * @param  Collection<string, Account>  $accounts
     */
    private function refreshBalances(Collection $accounts): void
    {
        foreach ($accounts as $account) {
            $entries = $this->orderedEntries($account);

            if ($entries->isEmpty()) {
                continue;
            }

            $balance = 0.0;

            foreach ($entries as $entry) {
                $balance += $this->signedValue($account, $entry);
                $entry->update(['balance_after' => $balance]);
            }

            $account->update(['display_balance' => $balance]);
        }

        foreach ($accounts as $account) {
            if ($account->subledger_id === null) {
                continue;
            }

            $account->update([
                'display_balance' => $accounts
                    ->where('ledger_id', $account->subledger_id)
                    ->sum('display_balance'),
            ]);
        }
    }

    /**
     * Seed the balance carried into the business date for every account with
     * backdated activity, so opening and closing balances line up.
     *
     * @param  Collection<string, Account>  $accounts
     */
    private function seedOpeningBalances(Collection $accounts, Carbon $businessDate): void
    {
        foreach ($accounts as $account) {
            $carriedIn = $this->orderedEntries($account)
                ->filter(fn (JournalEntry $entry) => $entry->transaction->effective_date->lt($businessDate))
                ->sum(fn (JournalEntry $entry) => $this->signedValue($account, $entry));

            if ((float) $carriedIn === 0.0) {
                continue;
            }

            AccountOpeningBalance::query()->updateOrCreate(
                [
                    'account_id' => $account->id,
                    'date' => $businessDate->toDateString(),
                ],
                ['balance' => $carriedIn],
            );
        }
    }

    /**
     * Posted entries for the account, ordered by effective date then insertion.
     *
     * @return Collection<int, JournalEntry>
     */
    private function orderedEntries(Account $account): Collection
    {
        return JournalEntry::query()
            ->where('account_id', $account->id)
            ->whereHas('transaction', fn ($query) => $query->where('status', 'POSTED'))
            ->with('transaction')
            ->get()
            ->sortBy(fn (JournalEntry $entry) => [
                $entry->transaction->effective_date->getTimestamp(),
                $entry->id,
            ])
            ->values();
    }

    /**
     * The entry amount expressed in the account's normal balance direction.
     */
    private function signedValue(Account $account, JournalEntry $entry): float
    {
        return $entry->type === $account->balance_type
            ? (float) $entry->value
            : -(float) $entry->value;
    }
}
