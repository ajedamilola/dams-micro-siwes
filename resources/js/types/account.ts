export type AccountType =
    | 'Asset'
    | 'Liability'
    | 'Equity'
    | 'Revenue'
    | 'Expense';

export type NormalBalance = 'debit' | 'credit';

export type AccountStatus = 'active' | 'inactive';

/**
 * One row of the chart of accounts. The first block of fields mirrors the
 * accounts table columns; the rest are presentation fields computed by the
 * controller (ledger labels, type, hierarchy metadata).
 */
export type ChartAccount = {
    id: string;
    code: string;
    name: string;
    description: string | null;
    parent_id: string | null;
    ledger_id: string;
    ledger_code: string | null;
    ledger_name: string | null;
    company_name: string | null;
    subledger_id: string | null;
    balance_type: NormalBalance;
    /** Computed balance from posted entries, in NGN. */
    account_balance: string;
    category: string | null;
    type: AccountType;
    status: AccountStatus;
    /** Depth in the parent/child tree, 0 for top-level accounts. */
    level: number;
    /** True when the account has child accounts (a header account). */
    is_group: boolean;
    children_count: number;
    created_at: string | null;
    updated_at: string | null;
};
