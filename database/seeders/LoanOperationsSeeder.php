<?php

namespace Database\Seeders;

use App\Enums\GeneralLedger\AccountCategory;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use Illuminate\Database\Seeder;

class LoanOperationsSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, description: string, category: AccountCategory, balance_type: string, parent?: string}>
     */
    private const ACCOUNTS = [
        [
            'code' => '1110',
            'name' => 'Loan Principal Portfolio',
            'description' => 'Portfolio reporting account for loan principal by product. Customer subledger carries the live balances.',
            'category' => AccountCategory::AccountsReceivable,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1120',
            'name' => 'Interest Receivable - Loans',
            'description' => 'Accrued loan interest not yet collected.',
            'category' => AccountCategory::AccountsReceivable,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1130',
            'name' => 'Penalty Receivable - Loans',
            'description' => 'Accrued loan penalties not yet collected.',
            'category' => AccountCategory::AccountsReceivable,
            'balance_type' => 'debit',
        ],
        [
            'code' => '4003',
            'name' => 'Loan Interest Income',
            'description' => 'Interest earned on loans.',
            'category' => AccountCategory::OperatingRevenue,
            'balance_type' => 'credit',
            'parent' => '4000',
        ],
        [
            'code' => '4004',
            'name' => 'Loan Fee Income',
            'description' => 'Processing and administrative fees on loans.',
            'category' => AccountCategory::OperatingRevenue,
            'balance_type' => 'credit',
            'parent' => '4000',
        ],
        [
            'code' => '4103',
            'name' => 'Penalty Income - Loans',
            'description' => 'Late-payment penalties on loans.',
            'category' => AccountCategory::OtherIncome,
            'balance_type' => 'credit',
            'parent' => '4100',
        ],
    ];

    public function run(): void
    {
        $companies = Company::query()->get();
        foreach ($companies as $company) {
            $master = Ledger::query()
                ->where('company_id', $company->id)
                ->where('code', 'MC')
                ->first();
            if (! $master) {
                continue;
            }

            $byCode = [];
            foreach (self::ACCOUNTS as $row) {
                $byCode[$row['code']] = Account::query()->firstOrCreate(
                    ['ledger_id' => $master->id, 'code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'balance_type' => $row['balance_type'],
                        'category' => $row['category']->value,
                        'status' => 'active',
                    ],
                );
            }

            // Enforce the intended tree on every run. Receivable accounts are
            // independent leaves; income accounts hang under their revenue
            // parents. This also repairs any stale parent/subledger links.
            foreach (self::ACCOUNTS as $row) {
                $parentId = null;
                if (isset($row['parent'])) {
                    $parentId = Account::query()
                        ->where('ledger_id', $master->id)
                        ->where('code', $row['parent'])
                        ->value('id');
                }

                $byCode[$row['code']]->update([
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'balance_type' => $row['balance_type'],
                    'category' => $row['category']->value,
                    'parent_id' => $parentId,
                    'subledger_id' => null,
                    'status' => 'active',
                ]);
            }
        }
    }
}
