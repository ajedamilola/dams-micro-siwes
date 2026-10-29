<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Enums\GeneralLedger\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\JournalTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    /** Entries can only change while the transaction is a draft. */
    public function store(Request $request, JournalTransaction $transaction)
    {
        self::assertDraft($transaction);

        $data = $request->validate([
            'account_id' => ['required', 'string', 'exists:accounts,id'],
            'type' => ['required', 'in:debit,credit'],
            'value' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
        ]);

        self::assertPostable((string) $data['account_id']);

        $transaction->entries()->create([
            'account_id' => $data['account_id'],
            'type' => $data['type'],
            'value' => $data['value'],
            'balance_after' => 0,
        ]);

        return back();
    }

    public function update(Request $request, JournalTransaction $transaction, JournalEntry $entry)
    {
        self::assertDraft($transaction);
        $journalEntry = self::scopedEntry($transaction, $entry);

        $data = $request->validate([
            'account_id' => ['sometimes', 'required', 'string', 'exists:accounts,id'],
            'type' => ['sometimes', 'required', 'in:debit,credit'],
            'value' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999999999'],
        ]);

        if (isset($data['account_id'])) {
            self::assertPostable((string) $data['account_id']);
        }

        $journalEntry->update($data);

        return back();
    }

    public function destroy(JournalTransaction $transaction, JournalEntry $entry)
    {
        self::assertDraft($transaction);
        self::scopedEntry($transaction, $entry)->delete();

        return back();
    }

    private static function assertDraft(JournalTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatus::DRAFT->value) {
            throw ValidationException::withMessages([
                'entry' => 'Only draft transactions can take entries.',
            ]);
        }
    }

    /** An entry can only change through its own transaction. */
    private static function scopedEntry(JournalTransaction $transaction, JournalEntry $entry): JournalEntry
    {
        abort_unless($entry->journal_transaction_id === $transaction->id, 404);

        return $entry;
    }

    /** Entries post to active leaf accounts only. Groups and control accounts are totals. */
    private static function assertPostable(string $accountId): void
    {
        $account = Account::findOrFail($accountId);

        if (($account->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'account_id' => 'Entries can only post to active accounts.',
            ]);
        }

        if ($account->subledger_id !== null || Account::where('parent_id', $account->id)->exists()) {
            throw ValidationException::withMessages([
                'account_id' => 'Post to a leaf account. Group and control accounts are totals.',
            ]);
        }
    }
}
