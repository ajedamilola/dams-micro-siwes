<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use App\Models\SystemSettings;
use App\Support\BalanceSheetLayout;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BalanceSheetLayoutSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default balance sheet headings. Headings reference accounts
     * by code here (readable and reseed-proof); only ids are stored.
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'DEMO')->firstOrFail();

        $ledgerIds = Ledger::query()->where('company_id', $company->id)->pluck('id');

        $accountsByCode = Account::query()
            ->whereIn('ledger_id', $ledgerIds)
            ->get()
            ->keyBy('code');

        SystemSettings::query()->updateOrCreate(
            ['name' => 'balance_sheet_layout'],
            ['value' => ['sections' => BalanceSheetLayout::resolveCodes($accountsByCode)]],
        );
    }
}
