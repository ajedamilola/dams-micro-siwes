export type InterestMethod = 'flat' | 'reducing';
export type RepaymentFrequency = 'weekly' | 'biweekly' | 'monthly';
export type LoanProductStatus = 'active' | 'inactive';
export type LoanStatus =
    | 'draft'
    | 'pending_approval'
    | 'approved'
    | 'active'
    | 'closed'
    | 'rejected'
    | 'written_off';

export type OperationType =
    | 'principal_receivable'
    | 'interest_receivable'
    | 'interest_income'
    | 'fee_income'
    | 'penalty_receivable'
    | 'penalty_income';

export interface ProductMapping {
    account_id: string;
    code?: string;
    name?: string;
}

export interface LoanProduct {
    id: string;
    code: string;
    name: string;
    company_id: number;
    company_name: string | null;
    description: string | null;
    interest_method: InterestMethod;
    annual_interest_rate: string;
    repayment_frequency: RepaymentFrequency;
    min_principal: string;
    max_principal: string;
    min_tenor: number;
    max_tenor: number;
    processing_fee_rate: string;
    penalty_rate_daily: string;
    penalty_grace_days: number;
    status: LoanProductStatus;
    mapping: Record<string, ProductMapping>;
    loans_count: number;
    recent_loans?: RecentLoan[];
}

export interface RecentLoan {
    id: string;
    loan_no: string;
    status: LoanStatus;
    principal: string;
    total_repayable: string;
    customer_name: string | null;
}

export interface ProductOption {
    id: string;
    code: string;
    name: string;
    company_id: number;
    company_name: string | null;
    interest_method: InterestMethod;
    annual_interest_rate: string;
    repayment_frequency: RepaymentFrequency;
    min_principal: string;
    max_principal: string;
    min_tenor: number;
    max_tenor: number;
    processing_fee_rate: string;
}

export interface LoanScheduleRow {
    id: string;
    installment_no: number;
    due_date: string;
    principal_due: string;
    interest_due: string;
    fee_due: string;
    penalty_due: string;
    total_due: string;
    principal_paid: string;
    interest_paid: string;
    fee_paid: string;
    penalty_paid: string;
    remaining: string;
    status: string;
}

export interface LoanRepaymentRow {
    id: string;
    amount: string;
    payment_date: string;
    principal_component: string;
    interest_component: string;
    fee_component: string;
    penalty_component: string;
    journal_transaction_id: string | null;
}

export interface LoanDetail {
    id: string;
    loan_no: string;
    company_id: number;
    status: LoanStatus;
    principal: string;
    annual_interest_rate: string;
    interest_method: InterestMethod;
    repayment_frequency: RepaymentFrequency;
    tenor: number;
    processing_fee: string;
    total_interest: string;
    total_repayable: string;
    disbursement_date: string | null;
    maturity_date: string | null;
    first_due_date: string | null;
    approved_at: string | null;
    disbursed_at: string | null;
    closed_at: string | null;
    disbursement_journal_id: string | null;
    product: { id: string; code: string; name: string } | null;
    customer: { id: string; customer_no: string; display_name: string } | null;
    outstanding: string;
    schedules: LoanScheduleRow[];
    repayments: LoanRepaymentRow[];
}

export interface CustomerSearchHit {
    id: string;
    customer_no: string;
    display_name: string;
    phone: string;
    kyc_status: string;
    account_id: string | null;
}

export const OPERATION_META: { value: OperationType; label: string; hint: string }[] = [
    { value: 'principal_receivable', label: 'Principal portfolio', hint: 'Asset · portfolio reporting account' },
    { value: 'interest_receivable', label: 'Interest receivable', hint: 'Asset · accrued interest' },
    { value: 'interest_income', label: 'Interest income', hint: 'Revenue · recognised on accrual' },
    { value: 'fee_income', label: 'Fee income', hint: 'Revenue · recognised at disbursement' },
    { value: 'penalty_receivable', label: 'Penalty receivable', hint: 'Asset · accrued penalties' },
    { value: 'penalty_income', label: 'Penalty income', hint: 'Revenue · recognised on penalty' },
];
