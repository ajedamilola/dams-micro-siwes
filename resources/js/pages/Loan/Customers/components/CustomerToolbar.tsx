import { SearchField } from '@/components/arc/search-field/search-field';
import { Select } from '@/components/arc/select/select';
import type { KycStatus, KycTier, LoanCustomerStatus } from '@/types/loan-customer';
import styles from './customers.module.css';

export type KycStatusFilter = KycStatus | 'all';
export type KycTierFilter = KycTier | 'all';
export type CustomerStatusFilter = LoanCustomerStatus | 'all';
export type CustomerTypeFilter = 'individual' | 'corporate' | 'group' | 'all';

interface CustomerToolbarProps {
    query: string;
    onQueryChange: (value: string) => void;
    kycStatusFilter: KycStatusFilter;
    onKycStatusChange: (value: KycStatusFilter) => void;
    kycTierFilter: KycTierFilter;
    onKycTierChange: (value: KycTierFilter) => void;
    statusFilter: CustomerStatusFilter;
    onStatusChange: (value: CustomerStatusFilter) => void;
    typeFilter: CustomerTypeFilter;
    onTypeChange: (value: CustomerTypeFilter) => void;
}

const KYC_STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All KYC statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'in_review', label: 'In review' },
    { value: 'verified', label: 'Verified' },
    { value: 'rejected', label: 'Rejected' },
];

const KYC_TIER_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All tiers' },
    { value: 'tier1', label: 'Tier 1' },
    { value: 'tier2', label: 'Tier 2' },
    { value: 'tier3', label: 'Tier 3' },
];

const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'blacklisted', label: 'Blacklisted' },
];

const TYPE_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All types' },
    { value: 'individual', label: 'Individual' },
    { value: 'corporate', label: 'Corporate' },
    { value: 'group', label: 'Group' },
];

export function CustomerToolbar({
    query,
    onQueryChange,
    kycStatusFilter,
    onKycStatusChange,
    kycTierFilter,
    onKycTierChange,
    statusFilter,
    onStatusChange,
    typeFilter,
    onTypeChange,
}: CustomerToolbarProps) {
    return (
        <div className={styles.toolbar} role="group" aria-label="Filter customers">
            <div className={styles.search}>
                <SearchField
                    label="Search customers"
                    placeholder="Search by name, number or phone"
                    value={query}
                    onValueChange={onQueryChange}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="KYC status"
                    value={kycStatusFilter}
                    onValueChange={(value) => onKycStatusChange(value as KycStatusFilter)}
                    options={KYC_STATUS_OPTIONS}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="KYC tier"
                    value={kycTierFilter}
                    onValueChange={(value) => onKycTierChange(value as KycTierFilter)}
                    options={KYC_TIER_OPTIONS}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Customer type"
                    value={typeFilter}
                    onValueChange={(value) => onTypeChange(value as CustomerTypeFilter)}
                    options={TYPE_OPTIONS}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Status"
                    value={statusFilter}
                    onValueChange={(value) => onStatusChange(value as CustomerStatusFilter)}
                    options={STATUS_OPTIONS}
                />
            </div>
        </div>
    );
}
