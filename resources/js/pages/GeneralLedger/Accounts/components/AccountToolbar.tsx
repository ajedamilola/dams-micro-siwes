import { SearchField } from '@/components/arc/search-field/search-field';
import { Select } from '@/components/arc/select/select';
import type { AccountStatus, AccountType } from '@/types/account';
import type { LedgerOption } from '@/types/ledger';
import styles from './accounts.module.css';

export type TypeFilter = AccountType | 'all';
export type StatusFilter = AccountStatus | 'all';
export type LedgerFilter = string;

interface AccountToolbarProps {
    query: string;
    onQueryChange: (value: string) => void;
    typeFilter: TypeFilter;
    onTypeChange: (value: TypeFilter) => void;
    statusFilter: StatusFilter;
    onStatusChange: (value: StatusFilter) => void;
    ledgers: LedgerOption[];
    ledgerFilter: LedgerFilter;
    onLedgerChange: (value: LedgerFilter) => void;
}

const TYPE_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All types' },
    { value: 'Asset', label: 'Asset' },
    { value: 'Liability', label: 'Liability' },
    { value: 'Equity', label: 'Equity' },
    { value: 'Revenue', label: 'Revenue' },
    { value: 'Expense', label: 'Expense' },
];

const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

export function AccountToolbar({
    query,
    onQueryChange,
    typeFilter,
    onTypeChange,
    statusFilter,
    onStatusChange,
    ledgers,
    ledgerFilter,
    onLedgerChange,
}: AccountToolbarProps) {
    return (
        <div
            className={styles.toolbar}
            role="group"
            aria-label="Filter accounts"
        >
            <div className={styles.search}>
                <SearchField
                    label="Search accounts"
                    placeholder="Search by code or name"
                    value={query}
                    onValueChange={onQueryChange}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Ledger"
                    value={ledgerFilter}
                    onValueChange={onLedgerChange}
                    options={[
                        { value: 'all', label: 'All ledgers' },
                        ...ledgers.map((ledger) => ({
                            value: ledger.id,
                            label: `${ledger.code} ${ledger.name}`,
                        })),
                    ]}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Account type"
                    value={typeFilter}
                    onValueChange={(value) => onTypeChange(value as TypeFilter)}
                    options={TYPE_OPTIONS}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Status"
                    value={statusFilter}
                    onValueChange={(value) =>
                        onStatusChange(value as StatusFilter)
                    }
                    options={STATUS_OPTIONS}
                />
            </div>
        </div>
    );
}
