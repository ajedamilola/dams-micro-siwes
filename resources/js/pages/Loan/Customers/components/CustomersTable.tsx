import { useMemo } from 'react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { DropdownMenu } from '@/components/arc/dropdown-menu/dropdown-menu';
import { EmptyState } from '@/components/arc/empty-state/empty-state';
import { Skeleton } from '@/components/arc/skeleton/skeleton';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { Ban, CircleCheck, Ellipsis, Eye, Pencil, SearchX, Trash } from 'lucide-react';
import { formatNGN } from '@/lib/format';
import { humanizeToken } from '@/lib/loan-customers';
import type { LoanCustomer } from '@/types/loan-customer';
import styles from './customers.module.css';

export type CustomerRow = LoanCustomer & {
    balance: number;
} & Record<string, unknown>;

const KYC_TONE: Record<LoanCustomer['kyc_status'], 'neutral' | 'info' | 'success' | 'danger'> = {
    pending: 'neutral',
    in_review: 'info',
    verified: 'success',
    rejected: 'danger',
};

const STATUS_TONE: Record<LoanCustomer['status'], 'success' | 'neutral' | 'danger'> = {
    active: 'success',
    inactive: 'neutral',
    blacklisted: 'danger',
};

const TYPE_TONE: Record<LoanCustomer['customer_type'], 'info' | 'neutral' | 'warning'> = {
    individual: 'info',
    corporate: 'neutral',
    group: 'warning',
};

const TIER_TONE: Record<LoanCustomer['kyc_tier'], 'neutral' | 'info' | 'success'> = {
    tier1: 'neutral',
    tier2: 'info',
    tier3: 'success',
};

function CustomerName({ row }: { row: CustomerRow }) {
    return (
        <span className={styles.nameCell}>
            <span className={styles.nameMain}>{row.display_name}</span>
            <span className={styles.nameSub}>{row.phone}</span>
        </span>
    );
}

interface CustomersTableProps {
    rows: CustomerRow[];
    totalFiltered: number;
    totalAll: number;
    onView: (row: CustomerRow) => void;
    onEdit: (row: CustomerRow) => void;
    onDelete: (row: CustomerRow) => void;
    onToggleStatus: (row: CustomerRow) => void;
    filtersActive: boolean;
    onClearFilters: () => void;
    loading: boolean;
}

export function CustomersTable({
    rows,
    totalFiltered,
    totalAll,
    onView,
    onEdit,
    onDelete,
    onToggleStatus,
    filtersActive,
    onClearFilters,
    loading,
}: CustomersTableProps) {
    const columns = useMemo<DataColumn<CustomerRow>[]>(
        () => [
            { key: 'customer_no', label: 'Number', width: 130 },
            {
                key: 'display_name',
                label: 'Customer',
                sortable: false,
                render: (_value, row) => <CustomerName row={row} />,
            },
            {
                key: 'customer_type',
                label: 'Type',
                render: (value, row) => (
                    <Badge tone={TYPE_TONE[row.customer_type]} size="sm">
                        {humanizeToken(String(value))}
                    </Badge>
                ),
            },
            {
                key: 'kyc_tier',
                label: 'Tier',
                render: (value, row) => (
                    <Badge tone={TIER_TONE[row.kyc_tier]} size="sm">
                        {humanizeToken(String(value))}
                    </Badge>
                ),
            },
            {
                key: 'kyc_status',
                label: 'KYC',
                render: (value, row) => (
                    <Badge tone={KYC_TONE[row.kyc_status]} size="sm">
                        {String(value).replace('_', ' ')}
                    </Badge>
                ),
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge tone={STATUS_TONE[row.status]} size="sm">
                        {String(value)}
                    </Badge>
                ),
            },
            {
                key: 'balance',
                label: 'Account balance',
                width: 150,
                render: (value) => <span>{formatNGN(value as number)}</span>,
            },
            {
                key: 'actions',
                label: 'Actions',
                sortable: false,
                render: (_value, row) => (
                    <DropdownMenu
                        label="Actions"
                        icon={<Ellipsis size={15} aria-hidden="true" />}
                        items={[
                            {
                                label: 'View',
                                icon: <Eye size={15} aria-hidden="true" />,
                                onSelect: () => onView(row),
                            },
                            {
                                label: 'Edit',
                                icon: <Pencil size={15} aria-hidden="true" />,
                                onSelect: () => onEdit(row),
                            },
                            row.status === 'active'
                                ? {
                                      label: 'Deactivate',
                                      icon: <Ban size={15} aria-hidden="true" />,
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () => onToggleStatus(row),
                                  }
                                : {
                                      label: 'Activate',
                                      icon: <CircleCheck size={15} aria-hidden="true" />,
                                      separatorBefore: true,
                                      onSelect: () => onToggleStatus(row),
                                  },
                            {
                                label: 'Delete',
                                icon: <Trash size={15} aria-hidden="true" />,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () => onDelete(row),
                            },
                        ]}
                    />
                ),
            },
        ],
        [onView, onEdit, onDelete, onToggleStatus],
    );

    return (
        <section aria-labelledby="customers-heading">
            <div className={styles.sectionHead}>
                <div>
                    <h2 id="customers-heading" className={styles.sectionTitle}>
                        Customers
                    </h2>
                    <p className={styles.count}>
                        {filtersActive
                            ? `${totalFiltered} of ${totalAll} customers`
                            : `${totalAll} customers`}
                    </p>
                </div>
                <div className={styles.sectionActions}>
                    {filtersActive ? (
                        <Button variant="secondary" size="sm" onClick={onClearFilters}>
                            Clear filters
                        </Button>
                    ) : null}
                </div>
            </div>

            <div className={styles.tableWrap}>
                {totalFiltered === 0 && !loading ? (
                    <EmptyState
                        icon={<SearchX size={24} />}
                        title="No customers match these filters"
                        description="Try a shorter search or clear the filters to see the full customer list."
                        action={
                            <Button variant="secondary" onClick={onClearFilters}>
                                Clear filters
                            </Button>
                        }
                    />
                ) : (
                    <Skeleton label="Loading customers" lines={6} loading={loading}>
                        <SortableDataTable
                            rows={rows}
                            columns={columns}
                            rowKey="id"
                            caption="Loan customers"
                            emptyMessage="No customers to show"
                            defaultSort={{ key: 'customer_no', direction: 'asc' }}
                            itemName={{ one: 'customer', other: 'customers' }}
                        />
                    </Skeleton>
                )}
            </div>
        </section>
    );
}
