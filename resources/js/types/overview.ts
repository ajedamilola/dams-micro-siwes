export interface OverviewFilters {
    from: string;
    to: string;
}

export interface LedgerOverviewStats {
    accounts: number;
    active_accounts: number;
    posted_count: number;
    pending_count: number;
    draft_count: number;
    debits: number;
    credits: number;
    net: number;
}

export interface LedgerActivityPoint {
    date: string;
    debits: number;
    credits: number;
    transactions: number;
}

export interface LedgerSlice {
    key: string;
    label: string;
    value: number;
}

export interface TopAccount {
    id: string;
    code: string;
    name: string;
    value: number;
}

export interface RecentTransaction {
    id: string;
    ref: string;
    description: string;
    effective_date: string | null;
    amount: number;
    entries: number;
}

export interface LedgerOverviewProps {
    filters: OverviewFilters;
    stats: LedgerOverviewStats;
    activity: LedgerActivityPoint[];
    ledgers: LedgerSlice[];
    top_accounts: TopAccount[];
    recent: RecentTransaction[];
}

export interface LoanOverviewStats {
    disbursed: number;
    disbursed_count: number;
    collected: number;
    collected_count: number;
    interest: number;
    fees: number;
    penalties: number;
    principal_collected: number;
    outstanding: number;
    active_loans: number;
    pending_approvals: number;
    overdue_loans: number;
}

export interface LoanActivityPoint {
    date: string;
    disbursed: number;
    collected: number;
}

export interface LoanSlice {
    key: string;
    label: string;
    value: number;
}

export interface LoanStatusSlice {
    key: string;
    label: string;
    count: number;
}

export interface RecentLoan {
    id: string;
    loan_no: string;
    customer: string;
    product: string | null;
    principal: number;
    status: string;
    disbursement_date: string | null;
    created_at: string | null;
}

export interface LoanOverviewProps {
    filters: OverviewFilters;
    stats: LoanOverviewStats;
    activity: LoanActivityPoint[];
    portfolio: LoanSlice[];
    status: LoanStatusSlice[];
    recent: RecentLoan[];
}
