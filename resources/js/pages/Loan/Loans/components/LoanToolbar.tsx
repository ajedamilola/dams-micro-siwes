import { SearchField } from '@/components/arc/search-field/search-field';
import { Select } from '@/components/arc/select/select';
import type { LoanStatus } from '@/types/loan';
import styles from './loans.module.css';

export type LoanStatusFilter = LoanStatus | 'all';

interface LoanToolbarProps {
    query: string;
    onQueryChange: (value: string) => void;
    statusFilter: LoanStatusFilter;
    onStatusChange: (value: LoanStatusFilter) => void;
}

export function LoanToolbar({ query, onQueryChange, statusFilter, onStatusChange }: LoanToolbarProps) {
    return (
        <div className={styles.toolbar} role="group" aria-label="Filter loans">
            <div className={styles.search}>
                <SearchField
                    label="Search loans"
                    placeholder="Search by loan number, product or customer"
                    value={query}
                    onValueChange={onQueryChange}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Status"
                    value={statusFilter}
                    onValueChange={(value) => onStatusChange(value as LoanStatusFilter)}
                    options={[
                        { value: 'all', label: 'All statuses' },
                        { value: 'pending_approval', label: 'Pending approval' },
                        { value: 'approved', label: 'Approved' },
                        { value: 'active', label: 'Active' },
                        { value: 'closed', label: 'Closed' },
                        { value: 'rejected', label: 'Rejected' },
                    ]}
                />
            </div>
        </div>
    );
}
