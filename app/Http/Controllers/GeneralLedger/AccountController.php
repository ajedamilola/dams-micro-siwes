<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Enums\GeneralLedger\AccountCategory;
use App\Http\Controllers\Controller;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalEntry;
use App\Models\GeneralLedger\Ledger;
use App\Services\GeneralLedger\AccountStatement;
use App\Support\BusinessDate;
use App\Support\SimpleXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::with('ledger.company')->orderBy('code')->get();

        return Inertia::render('GeneralLedger/Accounts/Index', [
            'accounts' => self::presentMany($accounts),
            'ledgers' => self::ledgerOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['string', 'nullable', 'sometimes'],
            'parent_id' => ['string', 'exists:accounts,id', 'sometimes', 'nullable'],
            'ledger_id' => ['required', 'string', 'exists:ledgers,id'],
            'subledger_id' => ['string', 'exists:ledgers,id', 'sometimes', 'nullable'],
            'balance_type' => ['required_without:parent_id', 'in:debit,credit'],
            'category' => ['required_without:parent_id', Rule::enum(AccountCategory::class)],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $code_exists = Account::where('ledger_id', $request->ledger_id)->where('code', $request->code)->exists();

        if ($code_exists) {
            // Standard Laravel error bag so the frontend error
            // handler can stay consistent.
            throw ValidationException::withMessages([
                'code' => 'This code is already in use in this ledger.',
            ]);
        }

        $balance_type = $request->input('balance_type');
        $category = $request->input('category');
        if ($request->input('parent_id')) {
            $parent = Account::find($request->parent_id);
            $category = $parent->category;
            $balance_type = $parent->balance_type;
        }

        $account = Account::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->input('description', ''),
            'balance_type' => $balance_type,
            'category' => $category instanceof AccountCategory ? $category->value : $category,
            'ledger_id' => $request->input('ledger_id'),
            'subledger_id' => $request->input('subledger_id'),
            'parent_id' => $request->input('parent_id'),
            'status' => $request->input('status', 'active'),
        ]);

        return redirect()->route('general-ledger.accounts.show', ['ledger' => $account->ledger_id, 'account' => $account->id]);
    }

    /**
     * Display the specified resource. The ledger id scopes the lookup
     * since codes repeat across ledgers.
     */
    public function show(Request $request, string $ledger, string $account)
    {
        $account = Account::with('ledger.company')->where('ledger_id', $ledger)->findOrFail($account);
        [$from, $to] = self::statementRange($request);
        $isLeaf = self::isLeaf($account);

        $ledgerAccounts = Account::where('ledger_id', $account->ledger_id)->orderBy('code')->get();

        $ancestors = [];
        $cursor = $account;
        while ($cursor->parent_id !== null) {
            $parent = $ledgerAccounts->firstWhere('id', $cursor->parent_id);
            if ($parent === null) {
                break;
            }
            array_unshift($ancestors, [
                'id' => $parent->id,
                'ledger_id' => $parent->ledger_id,
                'code' => $parent->code,
                'name' => $parent->name,
            ]);
            $cursor = $parent;
        }

        $children = $account->subledger_id ? Account::where('ledger_id', $account->subledger_id)->get() : $ledgerAccounts->where('parent_id', $account->id)->values();

        return Inertia::render('GeneralLedger/Accounts/Show', [
            'account' => self::present($account, $ledgerAccounts),
            'ancestors' => $ancestors,
            'children' => self::presentMany($children, $ledgerAccounts),
            'accounts' => $ledgerAccounts->map(fn (Account $row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'parent_id' => $row->parent_id,
                'is_group' => $ledgerAccounts->contains('parent_id', $row->id),
            ])->values(),
            'ledgers' => self::ledgerOptions(),
            'is_leaf' => $isLeaf,
            'statement' => $isLeaf ? app(AccountStatement::class)->build($account, $from, $to) : null,
            'statement_filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    public function exportStatement(Request $request, string $ledger, string $account)
    {
        $account = Account::with('ledger.company')->where('ledger_id', $ledger)->findOrFail($account);
        abort_unless(self::isLeaf($account), 404, 'Statements are only available for leaf accounts.');

        [$from, $to] = self::statementRange($request);
        $statement = app(AccountStatement::class)->build($account, $from, $to);
        $ledgerName = trim(($account->ledger?->code ?? '').' '.($account->ledger?->name ?? ''));

        $rows = [
            ['Account statement'],
            ["{$account->code} {$account->name}"],
            ["Ledger: {$ledgerName}"],
            ["Period: {$from->toDateString()} to {$to->toDateString()}"],
            [],
            ['Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'],
            [$from->toDateString(), '', 'Opening balance', null, null, $statement['opening_balance']],
        ];

        foreach ($statement['rows'] as $row) {
            $rows[] = [
                $row['date'],
                $row['ref'],
                $row['description'],
                $row['debit'],
                $row['credit'],
                $row['balance'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'Closing balance', $statement['debits'], $statement['credits'], $statement['closing_balance']];

        $filename = "statement-{$account->code}-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return SimpleXlsx::download($filename, $rows, [0, 5], [13, 16, 52, 16, 16, 18]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $ledger, string $account)
    {
        $account = Account::where('ledger_id', $ledger)->findOrFail($account);

        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('accounts')->where('ledger_id', $account->ledger_id)->ignore($account->id)],
            'description' => ['string', 'nullable', 'sometimes'],
            'parent_id' => ['string', 'exists:accounts,id', 'sometimes', 'nullable'],
            'subledger_id' => ['string', 'exists:ledgers,id', 'sometimes', 'nullable'],
            'balance_type' => ['sometimes', 'required', 'in:debit,credit'],
            'category' => ['sometimes', 'required', Rule::enum(AccountCategory::class)],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        // An account cannot parent itself or one of its descendants.
        if ($request->input('parent_id') && self::isDescendantOf($request->input('parent_id'), $account->id)) {
            throw ValidationException::withMessages([
                'parent_id' => 'An account cannot sit under itself or one of its sub-accounts.',
            ]);
        }

        $data = $request->only(['name', 'code', 'description', 'parent_id', 'subledger_id', 'balance_type', 'category', 'status']);
        if (array_key_exists('category', $data) && $data['category'] instanceof AccountCategory) {
            $data['category'] = $data['category']->value;
        }
        if (array_key_exists('parent_id', $data) && $data['parent_id']) {
            $parent = Account::find($data['parent_id']);
            $data['category'] = $parent->category;
            $data['balance_type'] = $parent->balance_type;
        }

        $account->update($data);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $ledger, string $account)
    {
        $account = Account::where('ledger_id', $ledger)->findOrFail($account);

        if ($account->childAccounts()->exists()) {
            return back()->withErrors(['account' => 'This account has sub-accounts. Move or delete them first.']);
        }

        if (JournalEntry::where('account_id', $account->id)->exists()) {
            return back()->withErrors(['account' => 'This account has journal entries and cannot be deleted.']);
        }

        $account->delete();

        return redirect()->route('general-ledger.accounts.index');
    }

    /**
     * Keyword search for the account picker. Returns up to 10 active
     * leaf accounts (groups and control accounts are totals, not postable).
     * Matching is relaxed: every word must appear in the code or the name,
     * case-insensitively.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $tokens = preg_split('/\s+/', strtolower(trim((string) ($data['q'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);

        // Group ids via a separate lookup: parent_id is stored as text
        // while id is a uuid, so they cannot be compared column-to-column
        // on Postgres.
        $groupIds = Account::whereNotNull('parent_id')->distinct()->pluck('parent_id')->all();

        $accounts = Account::where('status', 'active')
            ->whereNull('subledger_id')
            ->whereNotIn('id', $groupIds)
            ->when($tokens !== [], function ($query) use ($tokens) {
                $query->where(function ($query) use ($tokens) {
                    foreach ($tokens as $token) {
                        $query->where(function ($query) use ($token) {
                            $query->whereRaw('lower(code) like ?', ["%{$token}%"])
                                ->orWhereRaw('lower(name) like ?', ["%{$token}%"]);
                        });
                    }
                });
            })
            ->orderBy('code')
            ->limit(10)
            ->get(['id', 'code', 'name', 'balance_type', 'status', 'ledger_id']);

        return response()->json(['data' => $accounts]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function statementRange(Request $request): array
    {
        $data = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
        ]);

        $businessDate = BusinessDate::current();
        $from = CarbonImmutable::parse($data['from'] ?? $businessDate->subDays(29)->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? $businessDate->toDateString())->endOfDay();

        if ($to->gt($businessDate->endOfDay())) {
            $to = $businessDate->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    private static function isLeaf(Account $account): bool
    {
        return $account->subledger_id === null && ! $account->childAccounts()->exists();
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, array<string, mixed>>
     */
    private static function presentMany($accounts, $scope = null): array
    {
        $scope = $scope ?? $accounts;
        $childrenOf = [];
        foreach ($scope as $row) {
            if ($row->parent_id !== null) {
                $childrenOf[$row->parent_id][] = $row->id;
            }
        }

        return $accounts->map(fn (Account $account) => self::present($account, $scope, $childrenOf))->values()->all();
    }

    /**
     * @param  Collection<int, Account>  $scope
     * @param  array<string, array<int, string>>|null  $childrenOf
     * @return array<string, mixed>
     */
    private static function present(Account $account, $scope, $childrenOf = null): array
    {
        $byId = $scope->keyBy('id');
        $childrenOf = $childrenOf ?? (function () use ($scope) {
            $map = [];
            foreach ($scope as $row) {
                if ($row->parent_id !== null) {
                    $map[$row->parent_id][] = $row->id;
                }
            }

            return $map;
        })();

        $level = 0;
        $cursor = $account;
        while ($cursor->parent_id !== null && $byId->has($cursor->parent_id)) {
            $level++;
            $cursor = $byId->get($cursor->parent_id);
        }

        $children = $childrenOf[$account->id] ?? [];

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'description' => $account->description,
            'parent_id' => $account->parent_id,
            'ledger_id' => $account->ledger_id,
            'ledger_code' => $account->ledger?->code,
            'ledger_name' => $account->ledger?->name,
            'company_name' => $account->ledger?->company?->name,
            'subledger_id' => $account->subledger_id,
            'balance_type' => $account->balance_type,
            'account_balance' => (string) $account->account_balance,
            'category' => $account->category,
            'type' => self::typeFor($account->code, $account->category),
            'status' => $account->status ?? 'active',
            'level' => $level,
            'is_group' => count($children) > 0,
            'children_count' => count($children),
            'created_at' => $account->created_at?->toIso8601String(),
            'updated_at' => $account->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function ledgerOptions(): array
    {
        return Ledger::with('company')->withCount('accounts')->orderBy('code')->get()->map(fn (Ledger $ledger) => [
            'id' => $ledger->id,
            'code' => $ledger->code,
            'name' => $ledger->name,
            'company_id' => $ledger->company_id,
            'company_name' => $ledger->company?->name,
            'company_code' => $ledger->company?->code,
            'accounts_count' => $ledger->accounts_count,
        ])->values()->all();
    }

    private static function isDescendantOf(string $candidateId, string $accountId): bool
    {
        if ($candidateId === $accountId) {
            return true;
        }

        $cursor = Account::find($candidateId);
        while ($cursor && $cursor->parent_id !== null) {
            if ($cursor->parent_id === $accountId) {
                return true;
            }
            $cursor = Account::find($cursor->parent_id);
        }

        return false;
    }

    private static function typeFor(string $code, ?string $category): string
    {
        $byCategory = [
            'Asset' => ['CashAndCashEquivalents', 'AccountsReceivable', 'Inventory', 'PrepaidExpenses', 'OtherCurrentAssets', 'PropertyPlantAndEquipment', 'IntangibleAssets', 'LongTermInvestments', 'OtherNonCurrentAssets'],
            'Liability' => ['AccountsPayable', 'AccruedLiabilities', 'ShortTermDebt', 'DeferredRevenue', 'OtherCurrentLiabilities', 'LongTermDebt', 'LeaseLiabilities', 'OtherNonCurrentLiabilities'],
            'Equity' => ['ShareCapital', 'RetainedEarnings', 'Dividends', 'OtherEquity'],
            'Revenue' => ['OperatingRevenue', 'OtherIncome'],
            'Expense' => ['CostOfGoodsSold', 'OperatingExpenses', 'DepreciationAndAmortization', 'FinanceCosts', 'IncomeTaxExpense', 'OtherExpenses'],
        ];

        foreach ($byCategory as $type => $categories) {
            if (in_array($category, $categories, true)) {
                return $type;
            }
        }

        return match ($code[0] ?? '') {
            '1' => 'Asset',
            '2' => 'Liability',
            '3' => 'Equity',
            '4' => 'Revenue',
            default => 'Expense',
        };
    }
}
