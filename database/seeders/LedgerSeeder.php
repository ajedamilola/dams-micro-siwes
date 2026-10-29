<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GeneralLedger\Ledger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LedgerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'DEMO')->firstOrFail();

        $ledgers = [
            ['name' => 'Master Chart', 'code' => 'MC'],
            ['name' => 'Customers', 'code' => 'CUST'],
        ];

        foreach ($ledgers as $ledger) {
            Ledger::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $ledger['code'],
                ],
                ['name' => $ledger['name']],
            );
        }
    }
}
