<?php

namespace App\Services\GeneralLedger;

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Builds a leaf account ledger for a period: an opening balance line,
 * every posted movement in date order, and the resulting closing balance.
 * Balances follow the account's normal direction (debits positive for
 * debit-normal accounts, credits positive for credit-normal accounts).
 */
class AccountStatement
{
    /**
     * @return array{
     *     opening_balance: float,
     *     closing_balance: float,
     *     debits: float,
     *     credits: float,
     *     balance_type: string,
     *     rows: array<int, array<string, mixed>>
     * }
     */
    public function build(Account $account, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $opening = $this->openingBalance($account, $from);

        $entries = JournalEntry::query()
            ->select('id', 'account_id', 'type', 'value', 'journal_transaction_id')
            ->where('account_id', $account->id)
            ->whereHas('transaction', fn ($query) => $query
                ->where('status', 'POSTED')
                ->whereBetween('effective_date', [$from, $to->endOfDay()]))
            ->with('transaction:id,ref,description,effective_date,created_at')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (JournalEntry $entry) => $entry->transaction->effective_date?->timestamp ?? 0)
            ->values();

        $running = $opening;
        $debits = '0';
        $credits = '0';
        $rows = [];

        foreach ($entries as $entry) {
            $value = (string) $entry->value;
            $running = $entry->type === $account->balance_type
                ? bcadd($running, $value, 4)
                : bcsub($running, $value, 4);

            if ($entry->type === 'debit') {
                $debits = bcadd($debits, $value, 4);
            } else {
                $credits = bcadd($credits, $value, 4);
            }

            $rows[] = [
                'id' => $entry->id,
                'date' => $entry->transaction->effective_date?->toDateString(),
                'ref' => $entry->transaction->ref,
                'description' => $entry->transaction->description,
                'debit' => $entry->type === 'debit' ? (float) $value : null,
                'credit' => $entry->type === 'credit' ? (float) $value : null,
                'balance' => (float) $running,
            ];
        }

        return [
            'opening_balance' => (float) $opening,
            'closing_balance' => (float) $running,
            'debits' => (float) $debits,
            'credits' => (float) $credits,
            'balance_type' => $account->balance_type,
            'rows' => $rows,
        ];
    }

    private function openingBalance(Account $account, CarbonImmutable $from): string
    {
        $snapshot = $account->openingBalances()
            ->where('date', '<=', $from->toDateString())
            ->orderByDesc('date')
            ->first();

        $opening = $snapshot ? (string) $snapshot->balance : '0';
        $snapshotStart = $snapshot ? Carbon::parse($snapshot->date)->startOfDay() : null;

        $entries = JournalEntry::query()
            ->select('id', 'account_id', 'type', 'value')
            ->where('account_id', $account->id)
            ->whereHas('transaction', fn ($query) => $query
                ->where('status', 'POSTED')
                ->where('effective_date', '<', $from->startOfDay())
                ->when($snapshotStart, fn ($query) => $query->where('effective_date', '>=', $snapshotStart)))
            ->get();

        foreach ($entries as $entry) {
            $opening = $entry->type === $account->balance_type
                ? bcadd($opening, (string) $entry->value, 4)
                : bcsub($opening, (string) $entry->value, 4);
        }

        return $opening;
    }
}
