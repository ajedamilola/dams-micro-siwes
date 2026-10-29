<?php

namespace Database\Seeders;

use App\Enums\GeneralLedger\AccountCategory;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerAccountSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Customer accounts held in the Customers subledger, keyed by account code.
     *
     * @var array<string, string>
     */
    private const CUSTOMERS = [
        'CUST-001' => 'Adaeze Okafor',
        'CUST-002' => 'Tunde Bakare',
        'CUST-003' => 'Chiamaka Eze',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'DEMO')->firstOrFail();

        $customers = Ledger::query()
            ->where('company_id', $company->id)
            ->where('code', 'CUST')
            ->firstOrFail();

        foreach (self::CUSTOMERS as $code => $name) {
            Account::query()->firstOrCreate(
                [
                    'ledger_id' => $customers->id,
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'description' => "Customer account for {$name}.",
                    'balance_type' => 'credit',
                    'category' => AccountCategory::AccountsReceivable->value,
                ],
            );
        }
    }
}
