<?php

namespace App\Http\Controllers\GeneralLedger;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\SystemSettings;
use App\Support\BalanceSheetLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $company = Company::query()->orderBy('code')->firstOrFail();
        $ledgerIds = $company->ledgers()->pluck('ledgers.id');

        $accounts = Account::query()->whereIn('ledger_id', $ledgerIds)->orderBy('code')->get();

        $layout = ReportController::layout($accounts->keyBy('id'));
        $sections = array_map(fn (array $section) => [
            'key' => $section['key'],
            'label' => $section['label'],
            'headings' => $layout[$section['key']] ?? [],
        ], BalanceSheetLayout::sections());

        $mapped = [];
        foreach ($layout as $headings) {
            foreach ($headings as $heading) {
                $mapped = array_merge($mapped, BalanceSheetLayout::expandToLeaves($heading['account_ids'], $accounts->keyBy('id')));
            }
        }
        $mapped = array_unique($mapped);

        return Inertia::render('GeneralLedger/Settings/Index', [
            'company' => ['code' => $company->code, 'name' => $company->name],
            'sections' => $sections,
            'accounts' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
            ])->values(),
            'unmapped_accounts' => $accounts
                ->filter(fn (Account $account) => $account->subledger_id === null
                    && ! $accounts->contains(fn (Account $row) => $row->parent_id === $account->id)
                    && ! in_array($account->id, $mapped, true))
                ->map(fn (Account $account) => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sections' => ['required', 'array', 'size:'.count(BalanceSheetLayout::sectionKeys())],
            'sections.*.key' => ['required', Rule::in(BalanceSheetLayout::sectionKeys())],
            'sections.*.headings' => ['required', 'array'],
            'sections.*.headings.*.label' => ['required', 'string', 'max:100'],
            'sections.*.headings.*.account_ids' => ['present', 'array'],
            'sections.*.headings.*.account_ids.*' => ['string', 'distinct', 'exists:accounts,id'],
        ]);

        SystemSettings::query()->updateOrCreate(
            ['name' => 'balance_sheet_layout'],
            ['value' => ['sections' => array_map(fn (array $section) => [
                'key' => $section['key'],
                'headings' => array_values(array_map(fn (array $heading) => [
                    'label' => trim($heading['label']),
                    'account_ids' => array_values($heading['account_ids']),
                ], $section['headings'])),
            ], $data['sections'])]],
        );

        return back();
    }
}
