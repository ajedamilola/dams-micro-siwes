import type { AccountType } from '@/types/account';

export function accountUrl(id: string, ledgerId: string): string {
    return `/general-ledger/accounts/${ledgerId}/${id}`;
}

export function humanizeCategory(value: string): string {
    return value.replace(/([a-z0-9])([A-Z])/g, '$1 $2');
}

const CATEGORY_TYPES: Record<string, AccountType> = {
    CashAndCashEquivalents: 'Asset',
    AccountsReceivable: 'Asset',
    Inventory: 'Asset',
    PrepaidExpenses: 'Asset',
    OtherCurrentAssets: 'Asset',
    PropertyPlantAndEquipment: 'Asset',
    IntangibleAssets: 'Asset',
    LongTermInvestments: 'Asset',
    OtherNonCurrentAssets: 'Asset',
    AccountsPayable: 'Liability',
    AccruedLiabilities: 'Liability',
    ShortTermDebt: 'Liability',
    DeferredRevenue: 'Liability',
    OtherCurrentLiabilities: 'Liability',
    LongTermDebt: 'Liability',
    LeaseLiabilities: 'Liability',
    OtherNonCurrentLiabilities: 'Liability',
    ShareCapital: 'Equity',
    RetainedEarnings: 'Equity',
    Dividends: 'Equity',
    OtherEquity: 'Equity',
    OperatingRevenue: 'Revenue',
    OtherIncome: 'Revenue',
    CostOfGoodsSold: 'Expense',
    OperatingExpenses: 'Expense',
    DepreciationAndAmortization: 'Expense',
    FinanceCosts: 'Expense',
    IncomeTaxExpense: 'Expense',
    OtherExpenses: 'Expense',
};

export const ACCOUNT_CATEGORIES: { value: string; label: string }[] =
    Object.keys(CATEGORY_TYPES).map((value) => ({
        value,
        label: humanizeCategory(value),
    }));

/** Ids of the row itself plus every descendant, for cycle-safe parenting. */
export function subtreeIds(
    rows: { id: string; parent_id: string | null }[],
    id: string,
): string[] {
    const found = [id];
    let frontier = [id];
    while (frontier.length > 0) {
        const children = rows
            .filter((row) => row.parent_id && frontier.includes(row.parent_id))
            .map((row) => row.id);
        found.push(...children);
        frontier = children;
    }
    return found;
}
