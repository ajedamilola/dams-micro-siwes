<?php

namespace App\Models\GeneralLedger;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Guarded([])]
class Account extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function subledger()
    {
        return $this->belongsTo(Ledger::class, 'subledger_id');
    }

    public function parentAccount()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function childAccounts()
    {
        return $this->hasMany(Account::class, 'parent_id', 'id');
    }

    public function openingBalances()
    {
        return $this->hasMany(AccountOpeningBalance::class);
    }

    public function subledgerAccounts()
    {
        return $this->subledger->accounts();
    }

    /**
     * Computed balance from the latest posted journal entry, rolling
     * child (or subledger) balances into group accounts.
     */
    protected function accountBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                $latest_entry = JournalEntry::where('account_id', $this->id)->whereHas('transaction', function ($query) {
                    $query->where('status', 'POSTED');
                })->orderBy('id', 'desc')->first();

                $isControl = $this->subledger_id != null;

                $selfBalance = $latest_entry ? $latest_entry->balance_after : 0;

                $children = $isControl ? $this->subledgerAccounts()->get() : $this->childAccounts()->get();

                $isLeaf = $children->isEmpty();
                // base case
                if ($isLeaf) {
                    return $selfBalance;
                }

                $sum = $children->reduce(fn ($accumulator, $current) => bcadd($current->account_balance, $accumulator, 4), 0);

                return bcadd($selfBalance, $sum, 4);
            },
        );
    }

    public function accountBalanceAsOf(string $startDate, string $endDate)
    {
        $endDate = Carbon::parse($endDate ?: now());
        $start = Carbon::parse($startDate);

        $latest_snapshot_balance = $this->openingBalances()->where('date', '<=', $startDate)->latest('date')->first();

        // roll the snapshot forward with entries after its date and before the start date. reason: the recent snapshot date can be before the startDate but after
        // snapshot (if its available) basically transactions before startdate, but after the snapshot(if its available)
        $snapshotDelta = JournalEntry::where('account_id', $this->id)->whereHas('transaction', function ($query) use ($start, $latest_snapshot_balance) {
            $query->where('status', 'POSTED')
                ->where('effective_date', '<', $start)
                ->when($latest_snapshot_balance, fn ($query) => $query->where('effective_date', '>=', Carbon::parse($latest_snapshot_balance->date)));
        })->get()->reduce(fn ($accumulator, JournalEntry $entry) => $entry->type === $this->balance_type ? bcadd($accumulator, $entry->value, 4) : bcsub($accumulator, $entry->value, 4), '0');

        // get the closing(latest) balance based on the journal entry for that transaction for that day
        $latestJournalEntry = JournalEntry::where('account_id', $this->id)->whereHas('transaction', function ($query) use ($endDate) {
            $query->where('status', 'POSTED')->where('effective_date', '<=', $endDate->endOfDay());
        })->orderBy('id', 'desc')->first();

        $isControl = $this->subledger_id != null;
        $children = $isControl ? $this->subledgerAccounts()->get() : $this->childAccounts()->get();

        $isLeaf = $children->isEmpty();
        $selfBalances = [
            'opening_balance' => bcadd(($latest_snapshot_balance->balance ?? 0), $snapshotDelta, 4),
            'closing_balance' => $latestJournalEntry ? $latestJournalEntry->balance_after : 0,
        ];
        // base case
        if ($isLeaf) {
            return $selfBalances;
        }

        $sumBalances = $children->reduce(function ($accumulator, $current) use ($startDate, $endDate) {
            $prevBalance = $current->accountBalanceAsOf($startDate, $endDate);

            return [
                'opening_balance' => bcadd($prevBalance['opening_balance'], $accumulator['opening_balance'], 4),
                'closing_balance' => bcadd($prevBalance['closing_balance'], $accumulator['closing_balance'], 4),
            ];
        }, [
            'opening_balance' => '0',
            'closing_balance' => '0',
        ]);

        return [
            'opening_balance' => bcadd($selfBalances['opening_balance'], $sumBalances['opening_balance'], 4),
            'closing_balance' => bcadd($selfBalances['closing_balance'], $sumBalances['closing_balance'], 4),
        ];
    }
}
