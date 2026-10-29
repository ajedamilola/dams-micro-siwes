<?php

namespace App\Services\GeneralLedger;

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rolls each leaf account's closing balance into an opening balance
 * for the next business day. Snapshots are stored in the account's
 * normal direction, matching AccountOpeningBalance and the reports.
 */
class BalanceSnapshot
{
    public function closeInto(CarbonImmutable $closingBusinessDate, CarbonImmutable $openingDate): int
    {
        /** @var Collection<string, Account> $accounts */
        $accounts = Account::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        $childrenOf = [];
        foreach ($accounts as $account) {
            if ($account->parent_id !== null) {
                $childrenOf[$account->parent_id][] = $account->id;
            }
        }

        $leafIds = $accounts
            ->filter(fn (Account $account) => $account->subledger_id === null && empty($childrenOf[$account->id]))
            ->keys()
            ->all();

        if ($leafIds === []) {
            return 0;
        }

        $closingAt = $closingBusinessDate->endOfDay();

        $snapshots = AccountOpeningBalance::query()
            ->whereIn('account_id', $leafIds)
            ->where('date', '<=', $closingBusinessDate->toDateString())
            ->orderBy('date')
            ->get()
            ->groupBy('account_id');

        $entries = JournalEntry::query()
            ->select('id', 'account_id', 'type', 'value', 'journal_transaction_id')
            ->whereIn('account_id', $leafIds)
            ->whereHas('transaction', fn ($query) => $query
                ->where('status', 'POSTED')
                ->where('effective_date', '<=', $closingAt))
            ->with('transaction:id,effective_date')
            ->get();

        $written = 0;

        foreach ($leafIds as $leafId) {
            $account = $accounts->get($leafId);
            $snapshot = $snapshots->get($leafId)?->last();
            $normal = $snapshot ? (string) $snapshot->balance : '0';
            $snapshotStart = $snapshot ? Carbon::parse($snapshot->date)->startOfDay() : null;

            foreach ($entries->where('account_id', $leafId) as $entry) {
                if ($snapshotStart !== null && $entry->transaction->effective_date->lt($snapshotStart)) {
                    continue;
                }

                $normal = $entry->type === $account->balance_type
                    ? bcadd($normal, (string) $entry->value, 4)
                    : bcsub($normal, (string) $entry->value, 4);
            }

            AccountOpeningBalance::query()->updateOrCreate(
                [
                    'date' => $openingDate->toDateString(),
                    'account_id' => $leafId,
                ],
                ['balance' => $normal],
            );
            $written++;
        }

        return $written;
    }
}
