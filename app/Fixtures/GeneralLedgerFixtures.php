<?php

namespace App\Fixtures;

use Illuminate\Support\Str;

/**
 * Sample General Ledger data for the UI. Nothing here touches the
 * database: rows are shaped exactly like their tables (accounts,
 * journal_transactions, journal_entries) plus the presentation fields
 * the Inertia pages need.
 *
 * Assumptions (the schema has no status, type, or currency columns):
 * - Account "status" is a presentation-level field (active/inactive).
 * - Account "type" is derived from the category and code prefix
 *   (1xxx Asset, 2xxx Liability, 3xxx Equity, 4xxx Revenue, 5xxx Expense).
 * - Balances are NGN amounts; group accounts aggregate their descendants,
 *   mirroring Account::accountBalance().
 * - Entry "account_code", "account_name", and "account_balance_type" are
 *   denormalised for display; balance_after is a running balance per
 *   account across the fixture, in chronological order.
 */
class GeneralLedgerFixtures
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $accountsCache = null;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $transactionsCache = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function companies(): array
    {
        $now = now()->toIso8601String();

        return [
            ['id' => 1, 'name' => 'Dams Microfinance Bank', 'code' => 'DMFB', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Dams Asset Finance Ltd', 'code' => 'DAF', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Dams Insurance Brokers Ltd', 'code' => 'DIB', 'created_at' => $now, 'updated_at' => $now],
        ];
    }

    /**
     * Every company keeps its own chart of accounts; codes may repeat
     * across companies, so an account is identified by company plus code.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function accounts(): array
    {
        // Memoised: ids are random per build, so every caller in one
        // process must share the same chart (links, lookups, entries).
        if (self::$accountsCache === null) {
            self::$accountsCache = array_merge(
                self::buildChart(1, 'DMFB', 'Dams Microfinance Bank', self::primaryChartRows()),
                self::buildChart(2, 'DAF', 'Dams Asset Finance Ltd', self::assetFinanceChartRows()),
                self::buildChart(3, 'DIB', 'Dams Insurance Brokers Ltd', self::brokersChartRows()),
            );
        }

        return self::$accountsCache;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    private static function primaryChartRows(): array
    {
        // [code, name, description, parent_code, balance_type, balance, category, status]
        return [
            // Assets
            ['1000', 'Current Assets', 'Short term resources convertible to cash within a year', null, 'debit', '0', 'OtherCurrentAssets', 'active'],
            ['1100', 'Cash and Bank', 'Physical cash and bank balances', '1000', 'debit', '0', 'CashAndCashEquivalents', 'active'],
            ['1110', 'Main Operating Account', 'GTBank current account for daily operations', '1100', 'debit', '12450000.00', 'CashAndCashEquivalents', 'active'],
            ['1120', 'Payroll Account', 'First Bank account reserved for salary payments', '1100', 'debit', '3200500.75', 'CashAndCashEquivalents', 'active'],
            ['1140', 'Dormant Domiciliary Account', 'Unused USD account pending closure', '1100', 'debit', '0.00', 'CashAndCashEquivalents', 'inactive'],
            ['1200', 'Receivables and Prepayments', 'Amounts due from customers, staff, and prepaid costs', '1000', 'debit', '0', 'AccountsReceivable', 'active'],
            ['1210', 'Trade Receivables', 'Outstanding customer loan repayments and fees', '1200', 'debit', '8975250.50', 'AccountsReceivable', 'active'],
            ['1220', 'Staff Advances', 'Salary advances awaiting recovery (scheme suspended)', '1200', 'debit', '0.00', 'OtherCurrentAssets', 'inactive'],
            ['1230', 'Prepaid Rent', 'Unamortised balance of the head office lease', '1200', 'debit', '1800000.00', 'PrepaidExpenses', 'active'],
            ['1300', 'Inventory', 'Goods held for sale or use in operations', '1000', 'debit', '0', 'Inventory', 'active'],
            ['1310', 'Office Supplies Stock', 'Stationery and consumables on hand', '1300', 'debit', '412750.25', 'Inventory', 'active'],
            ['1500', 'Non-Current Assets', 'Long term resources used in the business', '1000', 'debit', '0', 'PropertyPlantAndEquipment', 'active'],
            ['1510', 'Property, Plant and Equipment', 'Land, buildings, and office equipment at cost', '1500', 'debit', '0', 'PropertyPlantAndEquipment', 'active'],
            ['1511', 'Office Building', 'Head office building at Victoria Island', '1510', 'debit', '35000000.00', 'PropertyPlantAndEquipment', 'active'],
            ['1512', 'Accumulated Depreciation', 'Contra account for depreciation to date', '1510', 'debit', '-2350000.00', 'PropertyPlantAndEquipment', 'active'],
            // Liabilities
            ['2000', 'Current Liabilities', 'Obligations due within one year', null, 'credit', '0', 'OtherCurrentLiabilities', 'active'],
            ['2100', 'Payables and Taxes', 'Supplier balances and statutory deductions', '2000', 'credit', '0', 'AccountsPayable', 'active'],
            ['2110', 'Trade Payables', 'Amounts owed to vendors and service providers', '2100', 'credit', '6720400.00', 'AccountsPayable', 'active'],
            ['2120', 'VAT Payable', 'Value added tax collected, net of input VAT', '2100', 'credit', '1150000.00', 'AccruedLiabilities', 'active'],
            ['2130', 'PAYE and Pension Payable', 'Unremitted PAYE, pension, and NHIS deductions', '2100', 'credit', '1335350.00', 'AccruedLiabilities', 'active'],
            ['2200', 'Short-Term Funding', 'Borrowings and customer deposits due within a year', '2000', 'credit', '0', 'ShortTermDebt', 'active'],
            ['2210', 'Bank Overdraft', 'GTBank overdraft facility in use', '2200', 'credit', '2000000.00', 'ShortTermDebt', 'active'],
            ['2220', 'Deferred Revenue', 'Fees received in advance of service delivery', '2200', 'credit', '750000.00', 'DeferredRevenue', 'active'],
            ['2500', 'Non-Current Liabilities', 'Obligations due after one year', '2000', 'credit', '0', 'LongTermDebt', 'active'],
            ['2510', 'Term Loan', 'Five year GTBank term loan for branch expansion', '2500', 'credit', '15000000.00', 'LongTermDebt', 'active'],
            // Equity
            ['3000', 'Equity', 'Residual interest and accumulated results', null, 'credit', '0', 'OtherEquity', 'active'],
            ['3100', 'Share Capital', 'Issued and fully paid ordinary shares', '3000', 'credit', '24011851.50', 'ShareCapital', 'active'],
            ['3200', 'Retained Earnings', 'Accumulated losses carried forward', '3000', 'credit', '-850000.00', 'RetainedEarnings', 'active'],
            // Revenue
            ['4000', 'Revenue', 'Income earned from operations and other sources', null, 'credit', '0', 'OperatingRevenue', 'active'],
            ['4100', 'Operating Income', 'Core lending and fee income', '4000', 'credit', '0', 'OperatingRevenue', 'active'],
            ['4110', 'Loan Interest Income', 'Interest earned on customer loans', '4100', 'credit', '18400000.00', 'OperatingRevenue', 'active'],
            ['4120', 'Fees and Commission Income', 'Processing, commitment, and service fees', '4100', 'credit', '4250000.00', 'OperatingRevenue', 'active'],
            ['4210', 'Other Income', 'Foreign exchange gains and sundry income', '4000', 'credit', '715500.00', 'OtherIncome', 'active'],
            // Expenses
            ['5000', 'Expenses', 'Costs incurred in generating revenue', null, 'debit', '0', 'OperatingExpenses', 'active'],
            ['5100', 'Personnel Costs', 'Staff related expenditure', '5000', 'debit', '0', 'OperatingExpenses', 'active'],
            ['5110', 'Salaries and Wages', 'Gross staff salaries and allowances', '5100', 'debit', '9800000.00', 'OperatingExpenses', 'active'],
            ['5120', 'Pension Contribution', 'Employer pension contribution under the CPS', '5100', 'debit', '980000.00', 'OperatingExpenses', 'active'],
            ['5200', 'Operating Costs', 'Day to day running costs', '5000', 'debit', '0', 'OperatingExpenses', 'active'],
            ['5210', 'Rent', 'Branch and head office rent', '5200', 'debit', '2400000.00', 'OperatingExpenses', 'active'],
            ['5220', 'Utilities and Bank Charges', 'Power, telecoms, and transaction charges', '5200', 'debit', '885400.00', 'OperatingExpenses', 'active'],
        ];
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    private static function assetFinanceChartRows(): array
    {
        // [code, name, description, parent_code, balance_type, balance, category, status]
        return [
            ['1000', 'Assets', 'Asset holdings of the company', null, 'debit', '0', 'OtherCurrentAssets', 'active'],
            ['1100', 'Cash and Bank', 'Bank balances', '1000', 'debit', '0', 'CashAndCashEquivalents', 'active'],
            ['1110', 'Operating Account', 'Wema Bank operating account', '1100', 'debit', '4200000.00', 'CashAndCashEquivalents', 'active'],
            ['1200', 'Receivables', 'Amounts due from lessees', '1000', 'debit', '0', 'AccountsReceivable', 'active'],
            ['1210', 'Lease Receivables', 'Outstanding lease rentals', '1200', 'debit', '11750000.00', 'AccountsReceivable', 'active'],
            ['2000', 'Liabilities', 'Obligations of the company', null, 'credit', '0', 'OtherCurrentLiabilities', 'active'],
            ['2100', 'Payables', 'Supplier balances', '2000', 'credit', '0', 'AccountsPayable', 'active'],
            ['2110', 'Trade Payables', 'Amounts owed to vendors', '2100', 'credit', '980000.00', 'AccountsPayable', 'active'],
            ['2500', 'Non-Current Liabilities', 'Obligations due after one year', '2000', 'credit', '0', 'LongTermDebt', 'active'],
            ['2510', 'Term Loan', 'Three year facility for asset purchases', '2500', 'credit', '8000000.00', 'LongTermDebt', 'active'],
            ['3000', 'Equity', 'Residual interest', null, 'credit', '0', 'OtherEquity', 'active'],
            ['3100', 'Share Capital', 'Issued and fully paid ordinary shares', '3000', 'credit', '5000000.00', 'ShareCapital', 'active'],
            ['4000', 'Revenue', 'Income earned from operations', null, 'credit', '0', 'OperatingRevenue', 'active'],
            ['4110', 'Lease Income', 'Rental income from leased assets', '4000', 'credit', '2150000.00', 'OperatingRevenue', 'active'],
            ['5000', 'Expenses', 'Costs incurred in generating revenue', null, 'debit', '0', 'OperatingExpenses', 'active'],
            ['5110', 'Staff Costs', 'Salaries and allowances', '5000', 'debit', '1100000.00', 'OperatingExpenses', 'active'],
            ['5210', 'Rent', 'Branch office rent', '5000', 'debit', '480000.00', 'OperatingExpenses', 'active'],
        ];
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    private static function brokersChartRows(): array
    {
        // [code, name, description, parent_code, balance_type, balance, category, status]
        return [
            ['1000', 'Assets', 'Asset holdings of the company', null, 'debit', '0', 'OtherCurrentAssets', 'active'],
            ['1100', 'Cash and Bank', 'Bank balances', '1000', 'debit', '0', 'CashAndCashEquivalents', 'active'],
            ['1110', 'Operating Account', 'Access Bank operating account', '1100', 'debit', '1850000.00', 'CashAndCashEquivalents', 'active'],
            ['1200', 'Receivables', 'Amounts due from insurers', '1000', 'debit', '0', 'AccountsReceivable', 'active'],
            ['1210', 'Commission Receivable', 'Outstanding brokerage commissions', '1200', 'debit', '640000.00', 'AccountsReceivable', 'active'],
            ['2000', 'Liabilities', 'Obligations of the company', null, 'credit', '0', 'OtherCurrentLiabilities', 'active'],
            ['2100', 'Payables', 'Supplier balances', '2000', 'credit', '0', 'AccountsPayable', 'active'],
            ['2110', 'Trade Payables', 'Amounts owed to vendors', '2100', 'credit', '220000.00', 'AccountsPayable', 'active'],
            ['3000', 'Equity', 'Residual interest', null, 'credit', '0', 'OtherEquity', 'active'],
            ['3100', 'Share Capital', 'Issued and fully paid ordinary shares', '3000', 'credit', '2000000.00', 'ShareCapital', 'active'],
            ['4000', 'Revenue', 'Income earned from operations', null, 'credit', '0', 'OtherIncome', 'active'],
            ['4120', 'Commission Income', 'Brokerage commissions earned', '4000', 'credit', '980000.00', 'OtherIncome', 'active'],
            ['5000', 'Expenses', 'Costs incurred in generating revenue', null, 'debit', '0', 'OperatingExpenses', 'active'],
            ['5110', 'Salaries', 'Staff salaries and allowances', '5000', 'debit', '720000.00', 'OperatingExpenses', 'active'],
        ];
    }

    /**
     * @param  array<int, array<int|string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function buildChart(int $companyId, string $companyCode, string $companyName, array $rows): array
    {
        $ledgerId = (string) Str::uuid();
        $ids = [];
        foreach ($rows as $row) {
            $ids[$row[0]] = (string) Str::uuid();
        }

        $now = now()->toIso8601String();
        $accounts = [];
        foreach ($rows as [$code, $name, $description, $parentCode, $balanceType, $balance, $category, $status]) {
            $accounts[$code] = [
                'id' => $ids[$code],
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'parent_id' => $parentCode === null ? null : $ids[$parentCode],
                'ledger_id' => $ledgerId,
                'subledger_id' => null,
                'company_id' => $companyId,
                'company_code' => $companyCode,
                'company_name' => $companyName,
                'balance_type' => $balanceType,
                'display_balance' => $balance,
                'category' => $category,
                'type' => self::typeFor($code, $category),
                'status' => $status,
                'level' => 0,
                'is_group' => false,
                'children_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Hierarchy metadata and group aggregation (children roll into parents).
        foreach ($accounts as $code => $account) {
            if ($account['parent_id'] !== null) {
                $parentCode = array_search($account['parent_id'], $ids, true);
                if ($parentCode !== false) {
                    $accounts[$parentCode]['is_group'] = true;
                    $accounts[$parentCode]['children_count']++;
                }
            }
        }
        foreach ($accounts as $code => $account) {
            $level = 0;
            $cursor = $account;
            while ($cursor['parent_id'] !== null) {
                $parentCode = array_search($cursor['parent_id'], $ids, true);
                if ($parentCode === false) {
                    break;
                }
                $level++;
                $cursor = $accounts[$parentCode];
            }
            $accounts[$code]['level'] = $level;
        }
        // Aggregate leaf balances into group accounts, deepest first.
        $ordered = array_keys($accounts);
        usort($ordered, fn ($a, $b) => $accounts[$b]['level'] <=> $accounts[$a]['level']);
        foreach ($ordered as $code) {
            $account = $accounts[$code];
            if ($account['parent_id'] === null) {
                continue;
            }
            $parentCode = array_search($account['parent_id'], $ids, true);
            if ($parentCode !== false) {
                $accounts[$parentCode]['display_balance'] = bcadd($accounts[$parentCode]['display_balance'], $account['display_balance'], 4);
            }
        }

        return array_values($accounts);
    }

    public static function typeFor(string $code, string $category): string
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

        return match ($code[0]) {
            '1' => 'Asset',
            '2' => 'Liability',
            '3' => 'Equity',
            '4' => 'Revenue',
            default => 'Expense',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $accounts
     * @return array<string, mixed>
     */
    public static function accountStats(array $accounts): array
    {
        $active = array_filter($accounts, fn ($account) => $account['status'] === 'active');
        $byType = [];
        foreach (['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'] as $type) {
            $count = count(array_filter($accounts, fn ($account) => $account['type'] === $type));
            $byType[] = ['type' => $type, 'count' => $count];
        }

        $totalFor = fn (string $type): string => array_reduce(
            $accounts,
            fn ($carry, $account) => $account['type'] === $type && $account['level'] === 0 ? bcadd((string) $carry, $account['display_balance'], 4) : $carry,
            '0'
        );

        return [
            'total' => count($accounts),
            'active' => count($active),
            'inactive' => count($accounts) - count($active),
            'by_type' => $byType,
            'asset_total' => $totalFor('Asset'),
            'liability_total' => $totalFor('Liability'),
        ];
    }

    /**
     * Sample journal transactions with balanced double entries, in
     * chronological order. Rows mirror journal_transactions plus
     * denormalised entry rows shaped like journal_entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function transactions(): array
    {
        if (self::$transactionsCache !== null) {
            return self::$transactionsCache;
        }

        $accounts = collect(self::accounts())
            ->where('company_code', 'DMFB')
            ->keyBy('code');

        // [ref, description, status, effective_date, posted_at, old_reversed_ref, entries]
        // entries: [account_code, type, value]
        $rows = [
            ['TXN-2026-0001', 'Loan disbursement batch, 1 Oct', 'POSTED', '2026-10-01T10:15:00+01:00', '2026-10-01T14:20:00+01:00', null, [
                ['1210', 'debit', '5000000.00'],
                ['1110', 'credit', '5000000.00'],
            ]],
            ['TXN-2026-0002', 'GTBank term loan drawdown', 'POSTED', '2026-10-02T09:00:00+01:00', '2026-10-02T11:05:00+01:00', null, [
                ['1110', 'debit', '15000000.00'],
                ['2510', 'credit', '15000000.00'],
            ]],
            ['TXN-2026-0003', 'September salaries and pension', 'POSTED', '2026-10-03T08:30:00+01:00', '2026-10-03T09:30:00+01:00', null, [
                ['5110', 'debit', '9800000.00'],
                ['5120', 'debit', '980000.00'],
                ['1120', 'credit', '9000000.00'],
                ['2130', 'credit', '1780000.00'],
            ]],
            ['TXN-2026-0004', 'Customer repayments, week 1', 'POSTED', '2026-10-04T15:00:00+01:00', '2026-10-04T16:45:00+01:00', null, [
                ['1110', 'debit', '3250000.00'],
                ['1210', 'credit', '2400000.00'],
                ['4110', 'credit', '850000.00'],
            ]],
            ['TXN-2026-0005', 'September VAT remittance', 'POSTED', '2026-10-06T09:45:00+01:00', '2026-10-06T10:15:00+01:00', null, [
                ['2120', 'debit', '1150000.00'],
                ['1110', 'credit', '1150000.00'],
            ]],
            ['TXN-2026-0006', 'Fee income, week 1 (duplicate)', 'REVERSED', '2026-10-06T14:00:00+01:00', '2026-10-06T15:00:00+01:00', null, [
                ['1110', 'debit', '420000.00'],
                ['4120', 'credit', '420000.00'],
            ]],
            ['TXN-2026-0007', 'Office supplies purchase', 'POSTED', '2026-10-07T12:30:00+01:00', '2026-10-07T13:40:00+01:00', null, [
                ['1310', 'debit', '385400.00'],
                ['2110', 'credit', '385400.00'],
            ]],
            ['TXN-2026-0008', 'Head office rent, Q4', 'POSTED', '2026-10-08T08:00:00+01:00', '2026-10-08T09:00:00+01:00', null, [
                ['5210', 'debit', '2400000.00'],
                ['1110', 'credit', '2400000.00'],
            ]],
            ['TXN-2026-0009', 'Foreign exchange gain', 'POSTED', '2026-10-09T16:00:00+01:00', '2026-10-09T17:20:00+01:00', null, [
                ['1110', 'debit', '620000.00'],
                ['4210', 'credit', '620000.00'],
            ]],
            ['TXN-2026-0010', 'Overdraft interest charge', 'SUBMITTED', '2026-10-10T11:30:00+01:00', null, null, [
                ['5220', 'debit', '275400.00'],
                ['2210', 'credit', '275400.00'],
            ]],
            ['TXN-2026-0011', 'Audit fee accrual', 'DRAFT', '2026-10-11T10:00:00+01:00', null, null, [
                ['5220', 'debit', '1250000.00'],
            ]],
            ['TXN-2026-0012', 'Reversal of TXN-2026-0006', 'POSTED', '2026-10-12T09:00:00+01:00', '2026-10-12T09:20:00+01:00', 'TXN-2026-0006', [
                ['4120', 'debit', '150000.00'],
                ['1110', 'credit', '150000.00'],
            ]],
        ];

        // Opening running balances per account before these entries.
        // The 3100/3200 bases plug the opening position so it balances.
        $running = self::openingBases();

        $transactions = [];
        $entryId = 0;
        foreach ($rows as [$ref, $description, $status, $effectiveDate, $postedAt, $reversedRef, $entries]) {
            $id = (string) Str::uuid();
            $built = [];
            $debits = '0';
            $credits = '0';
            foreach ($entries as [$code, $type, $value]) {
                $account = $accounts->get($code);
                $entryId++;
                $running[$code] = $type === $account['balance_type']
                    ? bcadd($running[$code] ?? '0', $value, 4)
                    : bcsub($running[$code] ?? '0', $value, 4);
                if ($type === 'debit') {
                    $debits = bcadd($debits, $value, 4);
                } else {
                    $credits = bcadd($credits, $value, 4);
                }
                $built[] = [
                    'id' => $entryId,
                    'journal_transaction_id' => $id,
                    'account_id' => $account['id'],
                    'account_ledger_id' => $account['ledger_id'],
                    'account_code' => $code,
                    'account_name' => $account['name'],
                    'account_balance_type' => $account['balance_type'],
                    'type' => $type,
                    'value' => $value,
                    'balance_after' => $running[$code],
                    'created_at' => $effectiveDate,
                    'updated_at' => $effectiveDate,
                ];
            }
            $transactions[] = [
                'id' => $id,
                'description' => $description,
                'ref' => $ref,
                'status' => $status,
                'old_reversed_transaction_id' => null, // resolved below
                'old_reversed_ref' => $reversedRef,
                'posted_at' => $postedAt,
                'effective_date' => $effectiveDate,
                'debit_total' => $debits,
                'credit_total' => $credits,
                'entries_count' => count($built),
                'entries' => $built,
                'created_at' => $effectiveDate,
                'updated_at' => $postedAt ?? $effectiveDate,
            ];
        }

        // Resolve reversal links to transaction ids.
        $byRef = collect($transactions)->keyBy('ref');
        foreach ($transactions as &$transaction) {
            if ($transaction['old_reversed_ref'] !== null) {
                $original = $byRef->get($transaction['old_reversed_ref']);
                $transaction['old_reversed_transaction_id'] = $original ? $original['id'] : null;
            }
            unset($transaction['old_reversed_ref']);
        }
        unset($transaction);

        self::$transactionsCache = $transactions;

        return $transactions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     */
    public static function findTransaction(array $transactions, string $ref): ?array
    {
        foreach ($transactions as $transaction) {
            if ($transaction['ref'] === $ref) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $accounts
     */
    public static function findAccountById(array $accounts, string $ledgerId, string $id): ?array
    {
        foreach ($accounts as $account) {
            if ($account['ledger_id'] === $ledgerId && $account['id'] === $id) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Opening balances per account code (primary company) before the
     * fixture entries. The 3100/3200 bases plug the opening position
     * so debits equal credits, as a real opening would.
     *
     * @return array<string, string>
     */
    private static function openingBases(): array
    {
        return [
            '1110' => '20000000.00',
            '1120' => '12200500.75',
            '1210' => '8125250.50',
            '1310' => '27350.25',
            '2110' => '6335000.00',
            '2120' => '2300000.00',
            '2130' => '3115350.00',
            '2210' => '2275400.00',
            '2510' => '0.00',
            '3100' => '24011851.50',
            '3200' => '-850000.00',
            '4110' => '0.00',
            '4120' => '3680000.00',
            '4210' => '95500.00',
            '5110' => '0.00',
            '5120' => '0.00',
            '5210' => '0.00',
            '5220' => '610000.00',
        ];
    }

    /**
     * Primary company accounts keyed by code.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function primaryAccounts(): array
    {
        $accounts = [];
        foreach (self::accounts() as $row) {
            if ($row['company_code'] === 'DMFB') {
                $accounts[$row['code']] = $row;
            }
        }

        return $accounts;
    }

    /**
     * Signed closing nets per account code (debits positive) from
     * opening balances plus posted entries. Drafts and submitted
     * transactions are not posted yet, so they are excluded.
     *
     * @return array<string, string>
     */
    private static function signedNets(): array
    {
        $accounts = self::primaryAccounts();
        $nets = [];
        foreach (self::openingBases() as $code => $base) {
            if (! isset($accounts[$code])) {
                continue;
            }
            $nets[$code] = $accounts[$code]['balance_type'] === 'debit'
                ? $base
                : bcsub('0', $base, 4);
        }
        foreach (self::transactions() as $transaction) {
            if ($transaction['status'] === 'DRAFT' || $transaction['status'] === 'SUBMITTED') {
                continue;
            }
            foreach ($transaction['entries'] as $entry) {
                $code = $entry['account_code'];
                // Signed space: debits are positive, credits negative.
                $delta = $entry['type'] === 'debit'
                    ? $entry['value']
                    : bcsub('0', $entry['value'], 4);
                $nets[$code] = bcadd($nets[$code] ?? '0', $delta, 4);
            }
        }

        return $nets;
    }

    /**
     * Trial balance for the primary company as at 12 Oct 2026.
     *
     * @return array<string, mixed>
     */
    public static function trialBalance(): array
    {
        $accounts = self::primaryAccounts();
        $nets = self::signedNets();
        ksort($nets);

        $rows = [];
        $debitTotal = '0';
        $creditTotal = '0';
        foreach ($nets as $code => $net) {
            $compare = bccomp($net, '0', 4);
            if ($compare === 0) {
                continue;
            }
            $debit = $compare > 0 ? $net : '0.0000';
            $credit = $compare < 0 ? bcsub('0', $net, 4) : '0.0000';
            $debitTotal = bcadd($debitTotal, $debit, 4);
            $creditTotal = bcadd($creditTotal, $credit, 4);
            $rows[] = [
                'code' => (string) $code,
                'name' => $accounts[$code]['name'],
                'account_id' => $accounts[$code]['id'],
                'account_ledger_id' => $accounts[$code]['ledger_id'],
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'company' => ['code' => 'DMFB', 'name' => 'Dams Microfinance Bank'],
            'as_at' => '2026-10-12T23:59:59+01:00',
            'rows' => $rows,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
        ];
    }

    /**
     * Balance sheet for the primary company as at 12 Oct 2026, derived
     * from the trial balance. Balances by construction: assets equal
     * liabilities plus equity plus the current period surplus.
     *
     * @return array<string, mixed>
     */
    public static function balanceSheet(): array
    {
        $accounts = self::primaryAccounts();
        $nets = self::signedNets();

        $line = function (string $code, string $amount) use ($accounts): array {
            return ['code' => $code, 'name' => $accounts[$code]['name'], 'amount' => $amount];
        };
        $inRange = fn (string $code, int $from, int $to): bool => (int) $code >= $from && (int) $code <= $to;

        $assetsCurrent = [];
        $assetsNonCurrent = [];
        $liabilitiesCurrent = [];
        $liabilitiesNonCurrent = [];
        $equityBase = [];
        $revenueExpense = '0';
        foreach ($nets as $code => $net) {
            if (bccomp($net, '0', 4) === 0) {
                continue;
            }
            $type = $accounts[$code]['type'];
            if ($type === 'Asset' && $inRange($code, 1100, 1499)) {
                $assetsCurrent[] = $line($code, $net);
            } elseif ($type === 'Asset') {
                $assetsNonCurrent[] = $line($code, $net);
            } elseif ($type === 'Liability' && $inRange($code, 2000, 2499)) {
                $liabilitiesCurrent[] = $line($code, bcsub('0', $net, 4));
            } elseif ($type === 'Liability') {
                $liabilitiesNonCurrent[] = $line($code, bcsub('0', $net, 4));
            } elseif ($type === 'Equity') {
                $equityBase[] = $line($code, bcsub('0', $net, 4));
            } else {
                $revenueExpense = bcadd($revenueExpense, $net, 4);
            }
        }

        $sum = fn (array $lines): string => array_reduce(
            $lines,
            fn ($carry, $line) => bcadd((string) $carry, $line['amount'], 4),
            '0'
        );
        $assetsCurrentTotal = $sum($assetsCurrent);
        $assetsNonCurrentTotal = $sum($assetsNonCurrent);
        $assetsTotal = bcadd($assetsCurrentTotal, $assetsNonCurrentTotal, 4);
        $liabilitiesCurrentTotal = $sum($liabilitiesCurrent);
        $liabilitiesNonCurrentTotal = $sum($liabilitiesNonCurrent);
        $liabilitiesTotal = bcadd($liabilitiesCurrentTotal, $liabilitiesNonCurrentTotal, 4);
        // Current period result: revenue less expenses (signed).
        $surplus = bcsub('0', $revenueExpense, 4);
        $equity = array_merge($equityBase, [
            [
                'code' => null,
                'name' => bccomp($surplus, '0', 4) >= 0 ? 'Current period surplus' : 'Current period deficit',
                'amount' => $surplus,
            ],
        ]);
        $equityTotal = bcadd($sum($equityBase), $surplus, 4);

        return [
            'company' => ['code' => 'DMFB', 'name' => 'Dams Microfinance Bank'],
            'as_at' => '2026-10-12T23:59:59+01:00',
            'assets_current' => $assetsCurrent,
            'assets_current_total' => $assetsCurrentTotal,
            'assets_noncurrent' => $assetsNonCurrent,
            'assets_noncurrent_total' => $assetsNonCurrentTotal,
            'assets_total' => $assetsTotal,
            'liabilities_current' => $liabilitiesCurrent,
            'liabilities_current_total' => $liabilitiesCurrentTotal,
            'liabilities_noncurrent' => $liabilitiesNonCurrent,
            'liabilities_noncurrent_total' => $liabilitiesNonCurrentTotal,
            'liabilities_total' => $liabilitiesTotal,
            'equity' => $equity,
            'equity_total' => $equityTotal,
        ];
    }
}
