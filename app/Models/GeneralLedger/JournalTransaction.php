<?php

namespace App\Models\GeneralLedger;

use App\Enums\GeneralLedger\TransactionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class JournalTransaction extends Model
{
    use HasUuids;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class, 'journal_transaction_id');
    }

    public function postTransaction()
    {
        try {
            DB::beginTransaction();
            // locking the transaction here incase i forget to lock from the caller
            $txn = JournalTransaction::where('id', $this->id)->lockForUpdate()->firstOrFail();
            // checking if the current transacion can move to posted
            $transaction_status = TransactionStatus::from($txn->status);
            if (! $transaction_status->canAdvance(TransactionStatus::POSTED)) {
                throw new BadRequestHttpException("Transaction cannot be posted from status {$transaction_status->value}");
            }
            $entries = $txn->entries()->orderBy('id')->get();
            // directly fetching the accounts and applying the locks once;
            $account_ids = $entries->pluck('account_id')->unique();
            Account::whereIn('id', $account_ids)->lockForUpdate()->orderBy('id')->get();
            $running_balances = [];
            $entries->each(function (JournalEntry $entry) use (&$running_balances) {
                $entry->updateAccountBalance($running_balances);
            });
            $txn->update([
                'status' => TransactionStatus::POSTED->value,
                'posted_at' => now(),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof BadRequestHttpException) {
                throw $th;
            }
            throw new BadRequestHttpException('An error occured, Cannot Post current transaction', $th);
        }

        $this->refresh();
    }

    /**
     * Reverse a posted transaction by booking a mirrored transaction and
     * posting it, then marking the original as reversed. The mirror nets
     * every balance back to where it was before the original posting.
     */
    public function reverseTransaction(string $ref, string $description, string $effectiveDate): JournalTransaction
    {
        try {
            DB::beginTransaction();
            // locking the transaction here incase i forget to lock from the caller
            $txn = JournalTransaction::where('id', $this->id)->lockForUpdate()->firstOrFail();
            // checking if the current transacion can move to reversed
            $transaction_status = TransactionStatus::from($txn->status);
            if (! $transaction_status->canAdvance(TransactionStatus::REVERSED)) {
                throw new BadRequestHttpException("Transaction cannot be reversed from status {$transaction_status->value}");
            }
            $entries = $txn->entries()->orderBy('id')->get();
            if ($entries->isEmpty()) {
                throw new BadRequestHttpException('Transaction has no entries to reverse.');
            }
            // the reversal starts submitted so it can be posted straight away
            $reversal = JournalTransaction::create([
                'description' => $description,
                'ref' => $ref,
                'status' => TransactionStatus::SUBMITTED->value,
                'old_reversed_transaction_id' => $txn->id,
                'effective_date' => $effectiveDate,
            ]);
            $entries->each(function (JournalEntry $entry) use ($reversal) {
                $reversal->entries()->create([
                    'account_id' => $entry->account_id,
                    'type' => $entry->type === 'debit' ? 'credit' : 'debit',
                    'value' => $entry->value,
                    'balance_after' => 0,
                ]);
            });
            // posting runs in a nested transaction of its own
            $reversal->postTransaction();
            $txn->update([
                'status' => TransactionStatus::REVERSED->value,
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof BadRequestHttpException) {
                throw $th;
            }
            throw new BadRequestHttpException('An error occured, Cannot Reverse current transaction', $th);
        }

        $this->refresh();

        return $reversal;
    }
}
