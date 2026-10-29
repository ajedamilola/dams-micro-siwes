<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OverviewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
        ]);

        $from = CarbonImmutable::parse($data['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? now()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(365)->startOfDay();
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $totals = DB::table('journal_entries as je')
            ->join('journal_transactions as txn', 'je.journal_transaction_id', '=', 'txn.id')
            ->where('txn.status', 'POSTED')
            ->whereBetween('txn.effective_date', [$from, $to])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN je.type = 'debit' THEN je.value ELSE 0 END), 0) as debits,
                COALESCE(SUM(CASE WHEN je.type = 'credit' THEN je.value ELSE 0 END), 0) as credits
            ")
            ->first();

        $debits = (float) ($totals->debits ?? 0);
        $credits = (float) ($totals->credits ?? 0);

        $daily = DB::table('journal_entries as je')
            ->join('journal_transactions as txn', 'je.journal_transaction_id', '=', 'txn.id')
            ->where('txn.status', 'POSTED')
            ->whereBetween('txn.effective_date', [$from, $to])
            ->selectRaw('DATE(txn.effective_date) as day, je.type as type, SUM(je.value) as total')
            ->groupBy('day', 'je.type')
            ->get()
            ->groupBy('day');

        $dailyCounts = DB::table('journal_transactions')
            ->where('status', 'POSTED')
            ->whereBetween('effective_date', [$from, $to])
            ->selectRaw('DATE(effective_date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $activity = [];
        for ($cursor = $from->startOfDay(); $cursor->lte($to); $cursor = $cursor->addDay()) {
            $key = $cursor->toDateString();
            $rows = $daily->get($key, collect())->keyBy('type');
            $activity[] = [
                'date' => $key,
                'debits' => round((float) ($rows->get('debit')?->total ?? 0), 2),
                'credits' => round((float) ($rows->get('credit')?->total ?? 0), 2),
                'transactions' => (int) ($dailyCounts[$key] ?? 0),
            ];
        }

        $ledgers = DB::table('journal_entries as je')
            ->join('journal_transactions as txn', 'je.journal_transaction_id', '=', 'txn.id')
            ->join('accounts', 'je.account_id', '=', 'accounts.id')
            ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
            ->where('txn.status', 'POSTED')
            ->whereBetween('txn.effective_date', [$from, $to])
            ->selectRaw('ledgers.name as label, SUM(je.value) as value')
            ->groupBy('ledgers.id', 'ledgers.name')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'key' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $row->label) ?: 'ledger'),
                'label' => $row->label,
                'value' => round((float) $row->value, 2),
            ])
            ->values()
            ->all();

        $topAccounts = DB::table('journal_entries as je')
            ->join('journal_transactions as txn', 'je.journal_transaction_id', '=', 'txn.id')
            ->join('accounts', 'je.account_id', '=', 'accounts.id')
            ->where('txn.status', 'POSTED')
            ->whereBetween('txn.effective_date', [$from, $to])
            ->selectRaw('accounts.id, accounts.code, accounts.name, SUM(je.value) as value')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'value' => round((float) $row->value, 2),
            ])
            ->all();

        $recent = JournalTransaction::query()
            ->where('status', 'POSTED')
            ->whereBetween('effective_date', [$from, $to])
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->limit(7)
            ->with('entries:id,journal_transaction_id,type,value')
            ->get()
            ->map(fn (JournalTransaction $transaction) => [
                'id' => $transaction->id,
                'ref' => $transaction->ref,
                'description' => $transaction->description,
                'effective_date' => $transaction->effective_date?->toDateString(),
                'amount' => round((float) $transaction->entries->where('type', 'debit')->sum('value'), 2),
                'entries' => $transaction->entries->count(),
            ])
            ->all();

        return Inertia::render('GeneralLedger/Overview', [
            'filters' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'stats' => [
                'accounts' => Account::query()->count(),
                'active_accounts' => Account::query()->where('status', 'active')->count(),
                'posted_count' => JournalTransaction::query()->where('status', 'POSTED')->whereBetween('effective_date', [$from, $to])->count(),
                'pending_count' => JournalTransaction::query()->where('status', 'SUBMITTED')->count(),
                'draft_count' => JournalTransaction::query()->where('status', 'DRAFT')->count(),
                'debits' => round($debits, 2),
                'credits' => round($credits, 2),
                'net' => round($debits - $credits, 2),
            ],
            'activity' => $activity,
            'ledgers' => $ledgers,
            'top_accounts' => $topAccounts,
            'recent' => $recent,
        ]);
    }
}
