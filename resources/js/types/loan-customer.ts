export type CustomerType = 'individual' | 'corporate' | 'group';
export type Gender = 'male' | 'female' | 'other';
export type MaritalStatus = 'single' | 'married' | 'divorced' | 'widowed';
export type KycTier = 'tier1' | 'tier2' | 'tier3';
export type KycStatus = 'pending' | 'in_review' | 'verified' | 'rejected';
export type RiskRating = 'low' | 'medium' | 'high';
export type LoanCustomerStatus = 'active' | 'inactive' | 'blacklisted';

export interface LoanCustomer {
    id: string;
    customer_no: string;
    company_id: number;
    company_name: string | null;
    account_id: string | null;
    account_code: string | null;
    account_balance: string;
    account_status: string | null;
    customer_type: CustomerType;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    display_name: string;
    business_name: string | null;
    rc_number: string | null;
    gender: string | null;
    date_of_birth: string | null;
    marital_status: string | null;
    phone: string;
    phone_alt: string | null;
    email: string | null;
    address: string;
    city: string | null;
    state: string | null;
    lga: string | null;
    nationality: string | null;
    bvn_masked: string | null;
    nin_masked: string | null;
    bvn: string | null;
    nin: string | null;
    means_of_id_type: string | null;
    means_of_id_number: string | null;
    id_expiry_date: string | null;
    proof_of_address_type: string | null;
    kyc_tier: KycTier;
    kyc_status: KycStatus;
    risk_rating: RiskRating;
    is_pep: boolean;
    employment_status: string | null;
    employer_name: string | null;
    monthly_income: string | null;
    next_of_kin_name: string | null;
    next_of_kin_relationship: string | null;
    next_of_kin_phone: string | null;
    status: LoanCustomerStatus;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface KycTierOption {
    value: KycTier;
    label: string;
    id_requirement: string;
    single_deposit_limit: number | null;
    cumulative_balance_limit: number | null;
    daily_transaction_limit: number | null;
}
