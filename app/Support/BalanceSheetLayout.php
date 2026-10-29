<?php

namespace App\Support;

use App\Models\GeneralLedger\Account;
use Illuminate\Support\Collection;

/**
 * The balance sheet groups accounts under admin-defined headings.
 * Headings are stored in system settings as account ids; this class
 * holds the fixed sections, the seeded default (by code, so it reads
 * well and survives reseeds), and the group/control expansion that
 * turns any mapped account into its postable leaves.
 */
class BalanceSheetLayout
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function sections(): array
    {
        return [
            ['key' => 'assets_current', 'label' => 'Current assets'],
            ['key' => 'assets_noncurrent', 'label' => 'Non-current assets'],
            ['key' => 'liabilities_current', 'label' => 'Current liabilities'],
            ['key' => 'liabilities_noncurrent', 'label' => 'Non-current liabilities'],
            ['key' => 'equity', 'label' => 'Equity'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function sectionKeys(): array
    {
        return array_column(self::sections(), 'key');
    }

    /**
     * Seeded default: headings with the account codes that make them up.
     *
     * @return array<int, array{key: string, headings: array<int, array{label: string, codes: array<int, string>}>}>
     */
    public static function defaultByCode(): array
    {
        return [
            [
                'key' => 'assets_current',
                'headings' => [
                    ['label' => 'Cash and Bank', 'codes' => ['1000']],
                    ['label' => 'Trade Receivables', 'codes' => ['1100', '1110', '1120', '1130']],
                    ['label' => 'Inventory', 'codes' => ['1200']],
                    ['label' => 'Prepaid Expenses', 'codes' => ['1300']],
                    ['label' => 'Other Current Assets', 'codes' => ['1400']],
                ],
            ],
            [
                'key' => 'assets_noncurrent',
                'headings' => [
                    ['label' => 'Property, Plant and Equipment', 'codes' => ['1500']],
                    ['label' => 'Intangible Assets', 'codes' => ['1600']],
                    ['label' => 'Long-term Investments', 'codes' => ['1700']],
                    ['label' => 'Other Non-current Assets', 'codes' => ['1800']],
                ],
            ],
            [
                'key' => 'liabilities_current',
                'headings' => [
                    ['label' => 'Trade Payables', 'codes' => ['2001']],
                    ['label' => 'Other Payables', 'codes' => ['2002']],
                    ['label' => 'Accrued Liabilities', 'codes' => ['2100']],
                    ['label' => 'Short-term Debt', 'codes' => ['2200']],
                    ['label' => 'Deferred Revenue', 'codes' => ['2300']],
                    ['label' => 'Other Current Liabilities', 'codes' => ['2400']],
                ],
            ],
            [
                'key' => 'liabilities_noncurrent',
                'headings' => [
                    ['label' => 'Long-term Debt', 'codes' => ['2500']],
                    ['label' => 'Lease Liabilities', 'codes' => ['2600']],
                    ['label' => 'Other Non-current Liabilities', 'codes' => ['2700']],
                ],
            ],
            [
                'key' => 'equity',
                'headings' => [
                    ['label' => 'Share Capital', 'codes' => ['3000']],
                    ['label' => 'Retained Earnings', 'codes' => ['3100']],
                    ['label' => 'Dividends', 'codes' => ['3200']],
                    ['label' => 'Other Equity', 'codes' => ['3300']],
                ],
            ],
        ];
    }

    /**
     * Turn the code-based default into the stored shape (account ids).
     * Unknown codes are skipped so a renamed chart cannot break seeding.
     *
     * @param  Collection<string, Account>  $accountsByCode
     * @return array<int, array{key: string, headings: array<int, array{label: string, account_ids: array<int, string>}>}>
     */
    public static function resolveCodes(Collection $accountsByCode): array
    {
        return array_map(fn (array $section) => [
            'key' => $section['key'],
            'headings' => array_map(fn (array $heading) => [
                'label' => $heading['label'],
                'account_ids' => array_values(array_filter(array_map(
                    fn (string $code) => $accountsByCode->get($code)?->id,
                    $heading['codes'],
                ))),
            ], $section['headings']),
        ], self::defaultByCode());
    }

    /**
     * Expand mapped accounts (groups, control accounts, or leaves) into
     * the postable leaf ids that actually carry balances. Unknown ids
     * are ignored so deleted accounts cannot break the report.
     *
     * @param  array<int, string>  $accountIds
     * @param  Collection<string, Account>  $accountsById
     * @return array<int, string>
     */
    public static function expandToLeaves(array $accountIds, Collection $accountsById): array
    {
        $childrenOf = [];
        $subledgerLeaves = [];
        foreach ($accountsById as $account) {
            if ($account->parent_id !== null) {
                $childrenOf[$account->parent_id][] = $account->id;
            }
            if ($account->subledger_id !== null) {
                continue;
            }
            $subledgerLeaves[$account->ledger_id][] = $account->id;
        }

        $leaves = [];
        $visit = function (string $id) use (&$visit, &$leaves, $accountsById, $childrenOf, $subledgerLeaves) {
            $account = $accountsById->get($id);
            if ($account === null || isset($leaves[$id])) {
                return;
            }
            if ($account->subledger_id !== null) {
                foreach ($subledgerLeaves[$account->subledger_id] ?? [] as $leafId) {
                    $visit($leafId);
                }

                return;
            }
            if (! empty($childrenOf[$id])) {
                foreach ($childrenOf[$id] as $childId) {
                    $visit($childId);
                }

                return;
            }
            $leaves[$id] = $id;
        };

        foreach ($accountIds as $id) {
            $visit((string) $id);
        }

        return array_values($leaves);
    }
}
