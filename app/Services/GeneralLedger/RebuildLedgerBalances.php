<?php

namespace App\Services\GeneralLedger;

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Recomputes running balances from posted entries in effective-date order,
 * rolls subledger totals into their control accounts, and refreshes the
 * opening balance carried into the business date. Used after seeding so
 * loan postings and GL postings agree.
 */
class RebuildLedgerBalances
{
    public function run(CarbonInterface $businessDate): void
    {
        /** @var Collection<int, Account> $accounts */
        $accounts = Account::query()->get();

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

        $this->seedOpeningBalances($accounts, $businessDate);
    }

    /**
     * @param  Collection<int, Account>  $accounts
     */
    private function seedOpeningBalances(Collection $accounts, CarbonInterface $businessDate): void
    {
        AccountOpeningBalance::query()
            ->where('date', $businessDate->toDateString())
            ->whereIn('account_id', $accounts->pluck('id'))
            ->delete();

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

    private function signedValue(Account $account, JournalEntry $entry): float
    {
        return $entry->type === $account->balance_type
            ? (float) $entry->value
            : -(float) $entry->value;
    }
}
