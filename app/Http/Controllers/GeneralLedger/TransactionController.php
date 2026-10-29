<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Enums\GeneralLedger\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\GeneralLedger\JournalTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::in([...TransactionStatus::caseValues(), 'all'])],
            'sort' => ['sometimes', 'nullable', Rule::in(['created_at', 'effective_date'])],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ]);

        $tokens = preg_split('/\s+/', strtolower(trim((string) ($filters['q'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);
        $status = $filters['status'] ?? 'all';
        // Default order is latest created first; the id tiebreak keeps
        // cursor paging deterministic in either direction.
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        // Latest first, then cursor-paginate over that ordering.
        $transactions = JournalTransaction::with('entries:id,journal_transaction_id,type,value')
            ->when($tokens !== [], function ($query) use ($tokens) {
                $query->where(function ($query) use ($tokens) {
                    foreach ($tokens as $token) {
                        $query->where(function ($query) use ($token) {
                            $query->whereRaw('lower(ref) like ?', ["%{$token}%"])
                                ->orWhereRaw('lower(description) like ?', ["%{$token}%"]);
                        });
                    }
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->cursorPaginate(10)
            ->withQueryString();

        return Inertia::render('GeneralLedger/Transactions/Index', [
            'transactions' => $transactions->through(fn (JournalTransaction $t) => self::present($t)),
            'stats' => self::stats(),
            'filters' => [
                'q' => (string) ($filters['q'] ?? ''),
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:5', 'max:255'],
            'effective_date' => ['required', 'date'],
        ]);

        $transaction = JournalTransaction::create([
            'description' => trim($data['description']),
            'ref' => self::nextRef(),
            'status' => TransactionStatus::DRAFT->value,
            'effective_date' => $data['effective_date'],
        ]);

        return redirect()->route('general-ledger.transactions.show', ['transaction' => $transaction->id]);
    }

    public function show(JournalTransaction $transaction)
    {
        $transaction->load('entries.account');

        $status = TransactionStatus::from($transaction->status);

        $reverses = $transaction->old_reversed_transaction_id
            ? JournalTransaction::select('id', 'ref')->find($transaction->old_reversed_transaction_id)
            : null;
        $reversedBy = JournalTransaction::select('id', 'ref')
            ->where('old_reversed_transaction_id', $transaction->id)
            ->first();

        return Inertia::render('GeneralLedger/Transactions/Show', [
            'transaction' => self::present($transaction, withEntries: true),
            'permissions' => [
                'editable' => $transaction->status === TransactionStatus::DRAFT->value,
                'can_submit' => $status->canAdvance(TransactionStatus::SUBMITTED),
                'can_cancel' => $status->canAdvance(TransactionStatus::CANCELLED),
                'can_post' => $status->canAdvance(TransactionStatus::POSTED),
                'can_reverse' => $status->canAdvance(TransactionStatus::REVERSED),
            ],
            'reverses' => $reverses ? ['id' => $reverses->id, 'ref' => $reverses->ref] : null,
            'reversed_by' => $reversedBy ? ['id' => $reversedBy->id, 'ref' => $reversedBy->ref] : null,
        ]);
    }

    public function update(Request $request, JournalTransaction $transaction)
    {
        $transaction->load('entries');

        $data = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'min:5', 'max:255'],
            'effective_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in([TransactionStatus::SUBMITTED->value, TransactionStatus::CANCELLED->value])],
        ]);

        // Only drafts can be edited. Status moves go through the same endpoint.
        if (isset($data['description']) || isset($data['effective_date'])) {
            if ($transaction->status !== TransactionStatus::DRAFT->value) {
                throw ValidationException::withMessages([
                    'description' => 'Only draft transactions can be edited.',
                ]);
            }
            $transaction->fill([
                'description' => isset($data['description']) ? trim($data['description']) : $transaction->description,
                'effective_date' => $data['effective_date'] ?? $transaction->effective_date,
            ]);
        }

        if (isset($data['status'])) {
            $next = TransactionStatus::from($data['status']);
            if (! TransactionStatus::from($transaction->status)->canAdvance($next)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot move from {$transaction->status} to {$next->value}.",
                ]);
            }
            if ($next === TransactionStatus::SUBMITTED) {
                self::assertSubmittable($transaction);
            }
            $transaction->status = $next->value;
        }

        $transaction->save();

        return back();
    }

    public function destroy(JournalTransaction $transaction)
    {
        if ($transaction->status !== TransactionStatus::DRAFT->value) {
            return back()->withErrors(['transaction' => 'Only draft transactions can be deleted.']);
        }

        $transaction->entries()->delete();
        $transaction->delete();

        return redirect()->route('general-ledger.transactions.index');
    }

    public function post(JournalTransaction $transaction)
    {
        if (! TransactionStatus::from($transaction->status)->canAdvance(TransactionStatus::POSTED)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted transactions can be posted.',
            ]);
        }

        try {
            $transaction->postTransaction();
        } catch (BadRequestHttpException $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }

        return back();
    }

    public function reverse(Request $request, JournalTransaction $transaction)
    {
        $data = $request->validate([
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'effective_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $description = trim((string) ($data['description'] ?? ''));

        try {
            $reversal = $transaction->reverseTransaction(
                self::nextRef(),
                $description !== '' ? $description : "Reversal of {$transaction->ref}",
                $data['effective_date'] ?? now()->toDateString(),
            );
        } catch (BadRequestHttpException $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }

        return redirect()->route('general-ledger.transactions.show', ['transaction' => $reversal->id]);
    }

    /** Next ref in the TXN-0001 sequence. */
    private static function nextRef(): string
    {
        $max = JournalTransaction::where('ref', 'like', 'TXN-%')
            ->pluck('ref')
            ->map(fn (string $ref) => (int) preg_replace('/\D+/', '', substr($ref, 4)) ?: 0)
            ->max() ?? 0;

        return sprintf('TXN-%04d', $max + 1);
    }

    /** A transaction cannot be submitted unless it balances. */
    private static function assertSubmittable(JournalTransaction $transaction): void
    {
        $totals = self::totals($transaction->entries);

        if ($transaction->entries->count() < 2) {
            throw ValidationException::withMessages([
                'status' => 'Add at least one debit and one credit before submitting.',
            ]);
        }

        if (bccomp($totals['debits'], '0', 4) <= 0 || bccomp($totals['debits'], $totals['credits'], 4) !== 0) {
            throw ValidationException::withMessages([
                'status' => 'Debits must equal credits before submitting.',
            ]);
        }
    }

    /** Global counts for the header cards, unaffected by paging. */
    private static function stats(): array
    {
        $counts = JournalTransaction::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        return [
            'total' => $counts->sum(),
            'draft' => $counts->get(TransactionStatus::DRAFT->value, 0),
            'submitted' => $counts->get(TransactionStatus::SUBMITTED->value, 0),
            'posted' => $counts->get(TransactionStatus::POSTED->value, 0),
        ];
    }

    /** @return array{debits: string, credits: string} */
    private static function totals($entries): array
    {
        $debits = '0';
        $credits = '0';
        foreach ($entries as $entry) {
            if ($entry->type === 'debit') {
                $debits = bcadd($debits, (string) $entry->value, 4);
            } else {
                $credits = bcadd($credits, (string) $entry->value, 4);
            }
        }

        return ['debits' => $debits, 'credits' => $credits];
    }

    /** @return array<string, mixed> */
    private static function present(JournalTransaction $transaction, bool $withEntries = false): array
    {
        $entries = $withEntries ? $transaction->entries : ($transaction->relationLoaded('entries') ? $transaction->entries : collect());
        $totals = self::totals($entries);

        return [
            'id' => $transaction->id,
            'description' => $transaction->description,
            'ref' => $transaction->ref,
            'status' => $transaction->status,
            'posted_at' => $transaction->posted_at?->toIso8601String(),
            'effective_date' => $transaction->effective_date?->toIso8601String(),
            'debit_total' => $totals['debits'],
            'credit_total' => $totals['credits'],
            'entries_count' => $withEntries ? $entries->count() : ($transaction->entries_count ?? $entries->count()),
            'entries' => $withEntries ? $entries->map(fn ($e) => [
                'id' => $e->id,
                'journal_transaction_id' => $e->journal_transaction_id,
                'account_id' => $e->account_id,
                'account_ledger_id' => $e->account?->ledger_id,
                'account_code' => $e->account?->code ?? '',
                'account_name' => $e->account?->name ?? '',
                'account_balance_type' => $e->account?->balance_type ?? 'debit',
                'type' => $e->type,
                'value' => (string) $e->value,
                'balance_after' => (string) ($e->balance_after ?? 0),
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
            ])->values()->all() : [],
            'created_at' => $transaction->created_at?->toIso8601String(),
            'updated_at' => $transaction->updated_at?->toIso8601String(),
        ];
    }
}
