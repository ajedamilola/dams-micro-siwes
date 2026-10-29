import { SearchField } from '@/components/arc/search-field/search-field';
import { Select } from '@/components/arc/select/select';
import styles from './products.module.css';

export type ProductMethodFilter = 'flat' | 'reducing' | 'all';
export type ProductFrequencyFilter = 'weekly' | 'biweekly' | 'monthly' | 'all';
export type ProductStatusFilter = 'active' | 'inactive' | 'all';

interface ProductToolbarProps {
    query: string;
    onQueryChange: (value: string) => void;
    methodFilter: ProductMethodFilter;
    onMethodChange: (value: ProductMethodFilter) => void;
    frequencyFilter: ProductFrequencyFilter;
    onFrequencyChange: (value: ProductFrequencyFilter) => void;
    statusFilter: ProductStatusFilter;
    onStatusChange: (value: ProductStatusFilter) => void;
}

export function ProductToolbar({
    query,
    onQueryChange,
    methodFilter,
    onMethodChange,
    frequencyFilter,
    onFrequencyChange,
    statusFilter,
    onStatusChange,
}: ProductToolbarProps) {
    return (
        <div className={styles.toolbar} role="group" aria-label="Filter products">
            <div className={styles.search}>
                <SearchField
                    label="Search products"
                    placeholder="Search by code or name"
                    value={query}
                    onValueChange={onQueryChange}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Interest method"
                    value={methodFilter}
                    onValueChange={(value) => onMethodChange(value as ProductMethodFilter)}
                    options={[
                        { value: 'all', label: 'All methods' },
                        { value: 'flat', label: 'Flat' },
                        { value: 'reducing', label: 'Reducing' },
                    ]}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Frequency"
                    value={frequencyFilter}
                    onValueChange={(value) => onFrequencyChange(value as ProductFrequencyFilter)}
                    options={[
                        { value: 'all', label: 'All frequencies' },
                        { value: 'weekly', label: 'Weekly' },
                        { value: 'biweekly', label: 'Bi-weekly' },
                        { value: 'monthly', label: 'Monthly' },
                    ]}
                />
            </div>
            <div className={styles.filter}>
                <Select
                    label="Status"
                    value={statusFilter}
                    onValueChange={(value) => onStatusChange(value as ProductStatusFilter)}
                    options={[
                        { value: 'all', label: 'All statuses' },
                        { value: 'active', label: 'Active' },
                        { value: 'inactive', label: 'Inactive' },
                    ]}
                />
            </div>
        </div>
    );
}
