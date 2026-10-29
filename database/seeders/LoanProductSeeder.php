<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use App\Models\Loan\LoanProduct;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    /**
     * Sample products covering both interest methods and all three
     * repayment frequencies. Every product maps all six operations.
     *
     * @var array<int, array<string, mixed>>
     */
    private const PRODUCTS = [
        [
            'code' => 'SAL',
            'name' => 'Salary Advance',
            'description' => 'Short-term salary-backed loans for employed earners. Flat interest, monthly instalments.',
            'interest_method' => 'flat',
            'annual_interest_rate' => 36,
            'repayment_frequency' => 'monthly',
            'min_principal' => 10000,
            'max_principal' => 1000000,
            'min_tenor' => 1,
            'max_tenor' => 12,
            'processing_fee_rate' => 2,
            'penalty_rate_daily' => 0.1,
            'penalty_grace_days' => 3,
        ],
        [
            'code' => 'GRP',
            'name' => 'Market Group Loan',
            'description' => 'Weekly group loans for traders and market associations. Flat interest, joint accountability.',
            'interest_method' => 'flat',
            'annual_interest_rate' => 48,
            'repayment_frequency' => 'weekly',
            'min_principal' => 5000,
            'max_principal' => 500000,
            'min_tenor' => 4,
            'max_tenor' => 52,
            'processing_fee_rate' => 1,
            'penalty_rate_daily' => 0.1,
            'penalty_grace_days' => 0,
        ],
        [
            'code' => 'SME',
            'name' => 'SME Growth Loan',
            'description' => 'Reducing-balance loans for small businesses. Interest falls as principal is repaid.',
            'interest_method' => 'reducing',
            'annual_interest_rate' => 30,
            'repayment_frequency' => 'monthly',
            'min_principal' => 100000,
            'max_principal' => 5000000,
            'min_tenor' => 3,
            'max_tenor' => 36,
            'processing_fee_rate' => 2.5,
            'penalty_rate_daily' => 0.15,
            'penalty_grace_days' => 7,
        ],
        [
            'code' => 'EMG',
            'name' => 'Emergency Loan',
            'description' => 'Small bi-weekly emergency advances for verified customers. Flat interest.',
            'interest_method' => 'flat',
            'annual_interest_rate' => 60,
            'repayment_frequency' => 'biweekly',
            'min_principal' => 5000,
            'max_principal' => 200000,
            'min_tenor' => 1,
            'max_tenor' => 6,
            'processing_fee_rate' => 3,
            'penalty_rate_daily' => 0.2,
            'penalty_grace_days' => 1,
        ],
    ];

    /**
     * Operation type to Master Chart account code.
     *
     * @var array<string, string>
     */
    private const MAPPING = [
        'principal_receivable' => '1110',
        'interest_receivable' => '1120',
        'interest_income' => '4003',
        'fee_income' => '4004',
        'penalty_receivable' => '1130',
        'penalty_income' => '4103',
    ];

    public function run(): void
    {
        foreach (Company::query()->get() as $company) {
            $master = Ledger::query()
                ->where('company_id', $company->id)
                ->where('code', 'MC')
                ->first();

            if (! $master) {
                continue;
            }

            $accountsByCode = Account::query()
                ->where('ledger_id', $master->id)
                ->whereIn('code', array_values(self::MAPPING))
                ->get()
                ->keyBy('code');

            if ($accountsByCode->count() < count(self::MAPPING)) {
                continue;
            }

            foreach (self::PRODUCTS as $row) {
                $product = LoanProduct::query()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $row['code']],
                    [...$row, 'status' => 'active'],
                );

                foreach (self::MAPPING as $operation => $code) {
                    $product->mappings()->updateOrCreate(
                        ['operation_type' => $operation],
                        ['account_id' => $accountsByCode->get($code)->id],
                    );
                }
            }
        }
    }
}
