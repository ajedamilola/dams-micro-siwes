import type { AccountStatus, NormalBalance } from './account';

export type TransactionStatus =
    | 'DRAFT'
    | 'SUBMITTED'
    | 'POSTED'
    | 'REVERSED'
    | 'CANCELLED';

export type TransactionSortKey = 'created_at' | 'effective_date';

export type SortDirection = 'asc' | 'desc';

export type EntryType = 'debit' | 'credit';

/**
 * One journal entry row. Mirrors journal_entries plus denormalised
 * account fields for display.
 */
export type JournalEntryRow = {
    id: number;
    journal_transaction_id: string;
    account_id: string;
    account_ledger_id: string;
    account_code: string;
    account_name: string;
    account_balance_type: NormalBalance;
    type: EntryType;
    /** Decimal value string, in NGN. */
    value: string;
    /** Running balance string after this entry, in NGN. */
    balance_after: string;
    created_at: string;
    updated_at: string;
};

/**
 * One journal transaction row. Mirrors journal_transactions plus
 * computed totals and nested entries.
 */
export type JournalTransactionRow = {
    id: string;
    description: string;
    ref: string;
    status: TransactionStatus;
    old_reversed_transaction_id: string | null;
    posted_at: string | null;
    effective_date: string;
    debit_total: string;
    credit_total: string;
    entries_count: number;
    entries: JournalEntryRow[];
    created_at: string;
    updated_at: string;
};

/** Compact account option for entry forms. */
export type AccountOption = {
    id: string;
    code: string;
    name: string;
    balance_type: NormalBalance;
    status: AccountStatus;
    ledger_id: string;
};

/** Sample company for the picker and filters. Mirrors companies. */
export type CompanyOption = {
    id: number;
    name: string;
    code: string;
};

/** Ledger for the chart filter and account form. Mirrors ledgers. */
export type LedgerOption = {
    id: string;
    code: string;
    name: string;
    company_id: number;
    company_name: string | null;
    company_code: string | null;
    accounts_count: number;
};

export type TrialBalanceLine = {
    code: string;
    name: string;
    account_id: string;
    account_ledger_id: string;
    debit: string;
    credit: string;
};

export type TrialBalanceStatement = {
    company: { code: string; name: string };
    as_at: string;
    rows: TrialBalanceLine[];
    debit_total: string;
    credit_total: string;
};

export type BalanceSheetLine = {
    code: string | null;
    name: string;
    amount: string;
};

export type BalanceSheetGroup = {
    label: string;
    lines: BalanceSheetLine[];
    total: string;
};

export type BalanceSheetStatement = {
    company: { code: string; name: string };
    as_at: string;
    assets_current: BalanceSheetGroup[];
    assets_current_total: string;
    assets_noncurrent: BalanceSheetGroup[];
    assets_noncurrent_total: string;
    assets_total: string;
    liabilities_current: BalanceSheetGroup[];
    liabilities_current_total: string;
    liabilities_noncurrent: BalanceSheetGroup[];
    liabilities_noncurrent_total: string;
    liabilities_total: string;
    equity: BalanceSheetGroup[];
    equity_total: string;
    surplus: BalanceSheetLine;
};

export type BalanceSheetHeading = {
    label: string;
    account_ids: string[];
};

export type BalanceSheetSection = {
    key: string;
    label: string;
    headings: BalanceSheetHeading[];
};

export type AccountRef = {
    id: string;
    code: string;
    name: string;
};

export type ReportFilters = {
    as_at: string;
};
