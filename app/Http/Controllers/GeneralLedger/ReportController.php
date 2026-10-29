<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\AccountOpeningBalance;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\SystemSettings;
use App\Support\BalanceSheetLayout;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Trial balance from opening balances plus posted journals.
     */
    public function trialBalance(Request $request)
    {
        $data = $request->validate([
            'as_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:'.BusinessDate::today()],
        ]);

        $company = self::company();
        $asAt = self::asAt($data['as_at'] ?? null);
        $report = self::balances($company, $asAt);

        $rows = [];
        $debitTotal = '0';
        $creditTotal = '0';
        foreach ($report['accounts']->sortBy('code') as $account) {
            if (! isset($report['balances'][$account->id])) {
                continue;
            }
            $net = $report['balances'][$account->id];
            if (bccomp($net, '0', 4) === 0) {
                continue;
            }
            if (bccomp($net, '0', 4) > 0) {
                $debit = $net;
                $credit = '0.0000';
            } else {
                $debit = '0.0000';
                $credit = bcsub('0', $net, 4);
            }
            $debitTotal = bcadd($debitTotal, $debit, 4);
            $creditTotal = bcadd($creditTotal, $credit, 4);
            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'account_id' => $account->id,
                'account_ledger_id' => $account->ledger_id,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return Inertia::render('GeneralLedger/Reports/TrialBalance', [
            'statement' => [
                'company' => ['code' => $company->code, 'name' => $company->name],
                'as_at' => $asAt->toIso8601String(),
                'rows' => $rows,
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal,
            ],
            'filters' => ['as_at' => $asAt->toDateString()],
            'business_date' => BusinessDate::today(),
        ]);
    }

    /**
     * Balance sheet from the trial balance, grouped under the headings
     * configured in settings. Unmapped balances flow into the surplus
     * plug, so the sheet balances by construction.
     */
    public function balanceSheet(Request $request)
    {
        $data = $request->validate([
            'as_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:'.BusinessDate::today()],
        ]);

        $company = self::company();
        $asAt = self::asAt($data['as_at'] ?? null);
        $report = self::balances($company, $asAt);
        $layout = self::layout($report['accounts']);

        $statement = [
            'company' => ['code' => $company->code, 'name' => $company->name],
            'as_at' => $asAt->toIso8601String(),
        ];
        foreach (BalanceSheetLayout::sections() as $section) {
            $groups = [];
            $sectionTotal = '0';
            foreach ($layout[$section['key']] ?? [] as $heading) {
                $leafIds = BalanceSheetLayout::expandToLeaves($heading['account_ids'], $report['accounts']);
                $lines = [];
                $headingTotal = '0';
                foreach ($leafIds as $leafId) {
                    $net = $report['balances'][$leafId] ?? '0';
                    if (bccomp($net, '0', 4) === 0) {
                        continue;
                    }
                    $account = $report['accounts']->get($leafId);
                    $amount = self::isCreditSection($section['key']) ? bcsub('0', $net, 4) : $net;
                    $headingTotal = bcadd($headingTotal, $amount, 4);
                    $lines[] = ['code' => $account->code, 'name' => $account->name, 'amount' => $amount];
                }
                usort($lines, fn (array $a, array $b) => strcmp((string) $a['code'], (string) $b['code']));
                $sectionTotal = bcadd($sectionTotal, $headingTotal, 4);
                $groups[] = ['label' => $heading['label'], 'lines' => $lines, 'total' => $headingTotal];
            }
            $statement[$section['key']] = $groups;
            $statement["{$section['key']}_total"] = $sectionTotal;
        }

        $assetsTotal = bcadd($statement['assets_current_total'], $statement['assets_noncurrent_total'], 4);
        $liabilitiesTotal = bcadd($statement['liabilities_current_total'], $statement['liabilities_noncurrent_total'], 4);
        $equityBase = $statement['equity_total'];
        // Current period result: revenue less expenses, via the plug.
        $surplus = bcsub($assetsTotal, bcadd($liabilitiesTotal, $equityBase, 4), 4);
        $statement['assets_total'] = $assetsTotal;
        $statement['liabilities_total'] = $liabilitiesTotal;
        $statement['surplus'] = [
            'code' => null,
            'name' => bccomp($surplus, '0', 4) >= 0 ? 'Current period surplus' : 'Current period deficit',
            'amount' => $surplus,
        ];
        $statement['equity_total'] = bcadd($equityBase, $surplus, 4);

        return Inertia::render('GeneralLedger/Reports/BalanceSheet', [
            'statement' => $statement,
            'filters' => ['as_at' => $asAt->toDateString()],
            'business_date' => BusinessDate::today(),
        ]);
    }

    private static function company(): Company
    {
        return Company::query()->orderBy('code')->firstOrFail();
    }

    private static function asAt(?string $asAt): Carbon
    {
        return Carbon::parse($asAt ?? BusinessDate::today())->endOfDay();
    }

    /**
     * Signed leaf balances (debits positive) as of the given moment:
     * each account's opening snapshot plus its posted movement after it.
     *
     * @return array{accounts: Collection<string, Account>, balances: array<string, string>}
     */
    private static function balances(Company $company, Carbon $asAt): array
    {
        $ledgerIds = $company->ledgers()->pluck('ledgers.id');

        /** @var Collection<string, Account> $accounts */
        $accounts = Account::query()->whereIn('ledger_id', $ledgerIds)->get()->keyBy('id');

        $childrenOf = [];
        foreach ($accounts as $account) {
            if ($account->parent_id !== null) {
                $childrenOf[$account->parent_id][] = $account->id;
            }
        }
        $leafIds = $accounts
            ->filter(fn (Account $account) => $account->subledger_id === null && empty($childrenOf[$account->id]))
            ->keys()->all();

        $snapshots = AccountOpeningBalance::query()
            ->whereIn('account_id', $leafIds)
            ->where('date', '<=', $asAt->toDateString())
            ->orderBy('date')
            ->get()
            ->groupBy('account_id');

        $entries = JournalEntry::query()
            ->select('id', 'account_id', 'type', 'value', 'journal_transaction_id')
            ->whereIn('account_id', $leafIds)
            ->whereHas('transaction', fn ($query) => $query
                ->where('status', 'POSTED')
                ->where('effective_date', '<=', $asAt))
            ->with('transaction:id,effective_date')
            ->get();

        $balances = [];
        foreach ($leafIds as $leafId) {
            $account = $accounts->get($leafId);
            $snapshot = $snapshots->get($leafId)?->last();
            // Snapshot and movement are tracked in the account's normal
            // direction, then flipped into signed space (debits positive).
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

            $balances[$leafId] = $account->balance_type === 'debit'
                ? $normal
                : bcsub('0', $normal, 4);
        }

        return ['accounts' => $accounts, 'balances' => $balances];
    }

    /**
     * Headings per section key, each with a label and account ids. Falls
     * back to the seeded default when settings were never saved.
     *
     * @param  Collection<string, Account>  $accounts
     * @return array<string, array<int, array{label: string, account_ids: array<int, string>}>>
     */
    public static function layout(Collection $accounts): array
    {
        $stored = SystemSettings::query()->where('name', 'balance_sheet_layout')->first()?->value;

        if (is_array($stored) && isset($stored['sections']) && is_array($stored['sections'])) {
            $layout = [];
            foreach ($stored['sections'] as $section) {
                if (! isset($section['key'], $section['headings']) || ! is_array($section['headings'])) {
                    continue;
                }
                $layout[$section['key']] = array_values(array_map(fn (array $heading) => [
                    'label' => (string) ($heading['label'] ?? ''),
                    'account_ids' => array_values(array_filter(
                        array_map('strval', (array) ($heading['account_ids'] ?? [])),
                        fn (string $id) => $accounts->has($id),
                    )),
                ], $section['headings']));
            }

            return $layout;
        }

        $byCode = $accounts->keyBy('code');

        return array_merge(...array_map(
            fn (array $section) => [$section['key'] => array_map(fn (array $heading) => [
                'label' => $heading['label'],
                'account_ids' => array_values(array_filter(array_map(
                    fn (string $code) => $byCode->get($code)?->id,
                    $heading['codes'],
                ))),
            ], $section['headings'])],
            BalanceSheetLayout::defaultByCode(),
        ));
    }

    private static function isCreditSection(string $key): bool
    {
        return str_starts_with($key, 'liabilities') || $key === 'equity';
    }
}
