<?php

namespace Database\Seeders;

use App\Enums\GeneralLedger\AccountCategory;
use App\Models\Company;
use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\Ledger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Standard chart of accounts seeded into the Master Chart. Header accounts have
     * child detail accounts beneath them, linked through the optional `parent` code.
     *
     * @var array<int, array{code: string, name: string, description: string, category: AccountCategory, balance_type: string, parent?: string, subledger?: string}>
     */
    private const ACCOUNTS = [
        // Assets
        [
            'code' => '1000',
            'name' => 'Cash and Cash Equivalents',
            'description' => 'Cash on hand and cash held at bank.',
            'category' => AccountCategory::CashAndCashEquivalents,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1001',
            'name' => 'Cash on Hand',
            'description' => 'Physical cash held at the business premises.',
            'category' => AccountCategory::CashAndCashEquivalents,
            'balance_type' => 'debit',
            'parent' => '1000',
        ],
        [
            'code' => '1002',
            'name' => 'Bank - Current Account',
            'description' => 'Main trading bank account.',
            'category' => AccountCategory::CashAndCashEquivalents,
            'balance_type' => 'debit',
            'parent' => '1000',
        ],
        [
            'code' => '1003',
            'name' => 'Bank - Savings Account',
            'description' => 'Savings bank account held for reserves.',
            'category' => AccountCategory::CashAndCashEquivalents,
            'balance_type' => 'debit',
            'parent' => '1000',
        ],
        [
            'code' => '1100',
            'name' => 'Customer Control',
            'description' => 'Control account for the individual customer accounts held in the Customers subledger.',
            'category' => AccountCategory::AccountsReceivable,
            'balance_type' => 'debit',
            'subledger' => 'CUST',
        ],
        [
            'code' => '1200',
            'name' => 'Inventory',
            'description' => 'Goods held for sale or for use in operations.',
            'category' => AccountCategory::Inventory,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1201',
            'name' => 'Raw Materials',
            'description' => 'Materials held for use in production.',
            'category' => AccountCategory::Inventory,
            'balance_type' => 'debit',
            'parent' => '1200',
        ],
        [
            'code' => '1202',
            'name' => 'Finished Goods',
            'description' => 'Goods ready for sale to customers.',
            'category' => AccountCategory::Inventory,
            'balance_type' => 'debit',
            'parent' => '1200',
        ],
        [
            'code' => '1300',
            'name' => 'Prepaid Expenses',
            'description' => 'Expenses paid before the period they relate to.',
            'category' => AccountCategory::PrepaidExpenses,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1301',
            'name' => 'Prepaid Rent',
            'description' => 'Rent paid in advance.',
            'category' => AccountCategory::PrepaidExpenses,
            'balance_type' => 'debit',
            'parent' => '1300',
        ],
        [
            'code' => '1302',
            'name' => 'Prepaid Insurance',
            'description' => 'Insurance premiums paid in advance.',
            'category' => AccountCategory::PrepaidExpenses,
            'balance_type' => 'debit',
            'parent' => '1300',
        ],
        [
            'code' => '1400',
            'name' => 'Other Current Assets',
            'description' => 'Current assets that do not fit another category.',
            'category' => AccountCategory::OtherCurrentAssets,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1500',
            'name' => 'Property, Plant and Equipment',
            'description' => 'Tangible assets used in operations.',
            'category' => AccountCategory::PropertyPlantAndEquipment,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1501',
            'name' => 'Motor Vehicles',
            'description' => 'Vehicles used in operations.',
            'category' => AccountCategory::PropertyPlantAndEquipment,
            'balance_type' => 'debit',
            'parent' => '1500',
        ],
        [
            'code' => '1502',
            'name' => 'Office Equipment',
            'description' => 'Equipment used in the office.',
            'category' => AccountCategory::PropertyPlantAndEquipment,
            'balance_type' => 'debit',
            'parent' => '1500',
        ],
        [
            'code' => '1503',
            'name' => 'Furniture and Fittings',
            'description' => 'Furniture and fittings used in operations.',
            'category' => AccountCategory::PropertyPlantAndEquipment,
            'balance_type' => 'debit',
            'parent' => '1500',
        ],
        [
            'code' => '1504',
            'name' => 'Accumulated Depreciation',
            'description' => 'Depreciation charged on property, plant and equipment to date.',
            'category' => AccountCategory::PropertyPlantAndEquipment,
            'balance_type' => 'credit',
            'parent' => '1500',
        ],
        [
            'code' => '1600',
            'name' => 'Intangible Assets',
            'description' => 'Non-physical assets held for use in operations.',
            'category' => AccountCategory::IntangibleAssets,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1601',
            'name' => 'Software',
            'description' => 'Capitalised software costs.',
            'category' => AccountCategory::IntangibleAssets,
            'balance_type' => 'debit',
            'parent' => '1600',
        ],
        [
            'code' => '1602',
            'name' => 'Licences and Permits',
            'description' => 'Capitalised licences and operating permits.',
            'category' => AccountCategory::IntangibleAssets,
            'balance_type' => 'debit',
            'parent' => '1600',
        ],
        [
            'code' => '1700',
            'name' => 'Long-term Investments',
            'description' => 'Investments intended to be held beyond one year.',
            'category' => AccountCategory::LongTermInvestments,
            'balance_type' => 'debit',
        ],
        [
            'code' => '1800',
            'name' => 'Other Non-current Assets',
            'description' => 'Non-current assets that do not fit another category.',
            'category' => AccountCategory::OtherNonCurrentAssets,
            'balance_type' => 'debit',
        ],

        // Liabilities
        [
            'code' => '2000',
            'name' => 'Accounts Payable',
            'description' => 'Amounts owed to suppliers for goods and services.',
            'category' => AccountCategory::AccountsPayable,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2001',
            'name' => 'Trade Payables',
            'description' => 'Amounts owed to suppliers for purchases.',
            'category' => AccountCategory::AccountsPayable,
            'balance_type' => 'credit',
            'parent' => '2000',
        ],
        [
            'code' => '2002',
            'name' => 'Other Payables',
            'description' => 'Payables that do not relate to trade purchases.',
            'category' => AccountCategory::AccountsPayable,
            'balance_type' => 'credit',
            'parent' => '2000',
        ],
        [
            'code' => '2100',
            'name' => 'Accrued Liabilities',
            'description' => 'Expenses incurred but not yet paid.',
            'category' => AccountCategory::AccruedLiabilities,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2200',
            'name' => 'Short-term Debt',
            'description' => 'Borrowings due within one year.',
            'category' => AccountCategory::ShortTermDebt,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2201',
            'name' => 'Bank Overdraft',
            'description' => 'Overdraft facility on the current account.',
            'category' => AccountCategory::ShortTermDebt,
            'balance_type' => 'credit',
            'parent' => '2200',
        ],
        [
            'code' => '2202',
            'name' => 'Short-term Loans',
            'description' => 'Loans repayable within one year.',
            'category' => AccountCategory::ShortTermDebt,
            'balance_type' => 'credit',
            'parent' => '2200',
        ],
        [
            'code' => '2300',
            'name' => 'Deferred Revenue',
            'description' => 'Income received in advance of being earned.',
            'category' => AccountCategory::DeferredRevenue,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2400',
            'name' => 'Other Current Liabilities',
            'description' => 'Current liabilities that do not fit another category.',
            'category' => AccountCategory::OtherCurrentLiabilities,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2500',
            'name' => 'Long-term Debt',
            'description' => 'Borrowings due after more than one year.',
            'category' => AccountCategory::LongTermDebt,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2501',
            'name' => 'Bank Term Loan',
            'description' => 'Term loan repayable after more than one year.',
            'category' => AccountCategory::LongTermDebt,
            'balance_type' => 'credit',
            'parent' => '2500',
        ],
        [
            'code' => '2600',
            'name' => 'Lease Liabilities',
            'description' => 'Present value of future lease payments.',
            'category' => AccountCategory::LeaseLiabilities,
            'balance_type' => 'credit',
        ],
        [
            'code' => '2700',
            'name' => 'Other Non-current Liabilities',
            'description' => 'Non-current liabilities that do not fit another category.',
            'category' => AccountCategory::OtherNonCurrentLiabilities,
            'balance_type' => 'credit',
        ],

        // Equity
        [
            'code' => '3000',
            'name' => 'Share Capital',
            'description' => 'Capital contributed by the owners.',
            'category' => AccountCategory::ShareCapital,
            'balance_type' => 'credit',
        ],
        [
            'code' => '3001',
            'name' => 'Ordinary Shares',
            'description' => 'Capital contributed through ordinary shares.',
            'category' => AccountCategory::ShareCapital,
            'balance_type' => 'credit',
            'parent' => '3000',
        ],
        [
            'code' => '3002',
            'name' => 'Preference Shares',
            'description' => 'Capital contributed through preference shares.',
            'category' => AccountCategory::ShareCapital,
            'balance_type' => 'credit',
            'parent' => '3000',
        ],
        [
            'code' => '3100',
            'name' => 'Retained Earnings',
            'description' => 'Accumulated profits retained in the business.',
            'category' => AccountCategory::RetainedEarnings,
            'balance_type' => 'credit',
        ],
        [
            'code' => '3200',
            'name' => 'Dividends',
            'description' => 'Distributions declared to the owners.',
            'category' => AccountCategory::Dividends,
            'balance_type' => 'debit',
        ],
        [
            'code' => '3201',
            'name' => 'Interim Dividends',
            'description' => 'Dividends declared during the year.',
            'category' => AccountCategory::Dividends,
            'balance_type' => 'debit',
            'parent' => '3200',
        ],
        [
            'code' => '3202',
            'name' => 'Final Dividends',
            'description' => 'Dividends declared at the end of the year.',
            'category' => AccountCategory::Dividends,
            'balance_type' => 'debit',
            'parent' => '3200',
        ],
        [
            'code' => '3300',
            'name' => 'Other Equity',
            'description' => 'Equity balances that do not fit another category.',
            'category' => AccountCategory::OtherEquity,
            'balance_type' => 'credit',
        ],

        // Revenue
        [
            'code' => '4000',
            'name' => 'Operating Revenue',
            'description' => 'Income from the principal business activities.',
            'category' => AccountCategory::OperatingRevenue,
            'balance_type' => 'credit',
        ],
        [
            'code' => '4001',
            'name' => 'Sales of Goods',
            'description' => 'Revenue from the sale of goods.',
            'category' => AccountCategory::OperatingRevenue,
            'balance_type' => 'credit',
            'parent' => '4000',
        ],
        [
            'code' => '4002',
            'name' => 'Service Income',
            'description' => 'Revenue from services rendered.',
            'category' => AccountCategory::OperatingRevenue,
            'balance_type' => 'credit',
            'parent' => '4000',
        ],
        [
            'code' => '4100',
            'name' => 'Other Income',
            'description' => 'Income outside the principal business activities.',
            'category' => AccountCategory::OtherIncome,
            'balance_type' => 'credit',
        ],
        [
            'code' => '4101',
            'name' => 'Interest Income',
            'description' => 'Interest earned on bank and other balances.',
            'category' => AccountCategory::OtherIncome,
            'balance_type' => 'credit',
            'parent' => '4100',
        ],
        [
            'code' => '4102',
            'name' => 'Sundry Income',
            'description' => 'Income that does not fit another category.',
            'category' => AccountCategory::OtherIncome,
            'balance_type' => 'credit',
            'parent' => '4100',
        ],

        // Expenses
        [
            'code' => '5000',
            'name' => 'Cost of Goods Sold',
            'description' => 'Direct costs of goods or services sold.',
            'category' => AccountCategory::CostOfGoodsSold,
            'balance_type' => 'debit',
        ],
        [
            'code' => '5001',
            'name' => 'Purchases',
            'description' => 'Cost of goods purchased for resale.',
            'category' => AccountCategory::CostOfGoodsSold,
            'balance_type' => 'debit',
            'parent' => '5000',
        ],
        [
            'code' => '5002',
            'name' => 'Freight and Carriage',
            'description' => 'Freight and carriage on goods purchased.',
            'category' => AccountCategory::CostOfGoodsSold,
            'balance_type' => 'debit',
            'parent' => '5000',
        ],
        [
            'code' => '5100',
            'name' => 'Operating Expenses',
            'description' => 'Costs of running day-to-day operations.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
        ],
        [
            'code' => '5101',
            'name' => 'Salaries and Wages',
            'description' => 'Staff salaries and wages.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5102',
            'name' => 'Rent',
            'description' => 'Rent on business premises.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5103',
            'name' => 'Utilities',
            'description' => 'Electricity, water and other utilities.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5104',
            'name' => 'Office Supplies',
            'description' => 'Consumables and stationery.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5105',
            'name' => 'Repairs and Maintenance',
            'description' => 'Repairs and maintenance of assets.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5106',
            'name' => 'Marketing and Advertising',
            'description' => 'Promotional and advertising costs.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5107',
            'name' => 'Travel and Transport',
            'description' => 'Staff travel and transport costs.',
            'category' => AccountCategory::OperatingExpenses,
            'balance_type' => 'debit',
            'parent' => '5100',
        ],
        [
            'code' => '5200',
            'name' => 'Depreciation and Amortization',
            'description' => 'Write-down of property, plant, equipment and intangible assets.',
            'category' => AccountCategory::DepreciationAndAmortization,
            'balance_type' => 'debit',
        ],
        [
            'code' => '5201',
            'name' => 'Depreciation Expense',
            'description' => 'Depreciation of property, plant and equipment.',
            'category' => AccountCategory::DepreciationAndAmortization,
            'balance_type' => 'debit',
            'parent' => '5200',
        ],
        [
            'code' => '5202',
            'name' => 'Amortization Expense',
            'description' => 'Amortization of intangible assets.',
            'category' => AccountCategory::DepreciationAndAmortization,
            'balance_type' => 'debit',
            'parent' => '5200',
        ],
        [
            'code' => '5300',
            'name' => 'Finance Costs',
            'description' => 'Interest and other costs of borrowing.',
            'category' => AccountCategory::FinanceCosts,
            'balance_type' => 'debit',
        ],
        [
            'code' => '5301',
            'name' => 'Interest Expense',
            'description' => 'Interest on borrowings.',
            'category' => AccountCategory::FinanceCosts,
            'balance_type' => 'debit',
            'parent' => '5300',
        ],
        [
            'code' => '5302',
            'name' => 'Bank Charges',
            'description' => 'Fees and charges levied by banks.',
            'category' => AccountCategory::FinanceCosts,
            'balance_type' => 'debit',
            'parent' => '5300',
        ],
        [
            'code' => '5400',
            'name' => 'Income Tax Expense',
            'description' => 'Tax expense for the period.',
            'category' => AccountCategory::IncomeTaxExpense,
            'balance_type' => 'debit',
        ],
        [
            'code' => '5401',
            'name' => 'Current Tax',
            'description' => 'Tax payable for the current period.',
            'category' => AccountCategory::IncomeTaxExpense,
            'balance_type' => 'debit',
            'parent' => '5400',
        ],
        [
            'code' => '5402',
            'name' => 'Deferred Tax',
            'description' => 'Deferred tax expense for the period.',
            'category' => AccountCategory::IncomeTaxExpense,
            'balance_type' => 'debit',
            'parent' => '5400',
        ],
        [
            'code' => '5500',
            'name' => 'Other Expenses',
            'description' => 'Expenses that do not fit another category.',
            'category' => AccountCategory::OtherExpenses,
            'balance_type' => 'debit',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'DEMO')->firstOrFail();

        $masterChart = Ledger::query()
            ->where('company_id', $company->id)
            ->where('code', 'MC')
            ->firstOrFail();

        $customers = Ledger::query()
            ->where('company_id', $company->id)
            ->where('code', 'CUST')
            ->firstOrFail();

        $subledgers = [
            'CUST' => $customers->id,
        ];

        $accounts = [];

        foreach (self::ACCOUNTS as $account) {
            $accounts[$account['code']] = Account::query()->firstOrCreate(
                [
                    'ledger_id' => $masterChart->id,
                    'code' => $account['code'],
                ],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'balance_type' => $account['balance_type'],
                    'category' => $account['category']->value,
                    'subledger_id' => isset($account['subledger']) ? $subledgers[$account['subledger']] : null,
                ],
            );
        }

        foreach (self::ACCOUNTS as $account) {
            if (! isset($account['parent'])) {
                continue;
            }

            $accounts[$account['code']]->update([
                'parent_id' => $accounts[$account['parent']]->id,
            ]);
        }
    }
}
