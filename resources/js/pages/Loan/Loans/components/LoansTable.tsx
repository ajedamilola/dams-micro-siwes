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
import { Ellipsis, Eye, HandCoins, SearchX } from 'lucide-react';
import { formatNGN } from '@/lib/format';
import styles from './loans.module.css';

export interface LoanBrief {
    id: string;
    loan_no: string;
    status: string;
    principal: string;
    total_repayable: string;
    product_name: string | null;
    product_code?: string;
    customer_name: string | null;
    customer_no?: string;
    maturity_date?: string | null;
}

export type LoanBriefRow = LoanBrief & Record<string, unknown>;

const STATUS_TONE: Record<string, 'success' | 'info' | 'neutral' | 'danger' | 'warning'> = {
    draft: 'neutral',
    pending_approval: 'info',
    approved: 'info',
    active: 'success',
    closed: 'neutral',
    rejected: 'danger',
    written_off: 'danger',
};

export function statusTone(status: string): 'success' | 'info' | 'neutral' | 'danger' | 'warning' {
    return STATUS_TONE[status] ?? 'neutral';
}

function LoanName({ row }: { row: LoanBriefRow }) {
    return (
        <span className={styles.nameCell}>
            <span className={styles.nameMain}>{row.loan_no}</span>
            <span className={styles.nameSub}>{(row.customer_name ?? 'Unknown customer') as string}</span>
        </span>
    );
}

interface LoansTableProps {
    rows: LoanBriefRow[];
    totalFiltered: number;
    totalAll: number;
    onView: (row: LoanBriefRow) => void;
    filtersActive: boolean;
    onClearFilters: () => void;
    loading: boolean;
}

export function LoansTable({ rows, totalFiltered, totalAll, onView, filtersActive, onClearFilters, loading }: LoansTableProps) {
    const columns = useMemo<DataColumn<LoanBriefRow>[]>(
        () => [
            {
                key: 'loan_no',
                label: 'Loan',
                sortable: false,
                render: (_value, row) => <LoanName row={row} />,
            },
            { key: 'product_name', label: 'Product', sortable: false },
            {
                key: 'principal',
                label: 'Principal',
                width: 150,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'total_repayable',
                label: 'Repayable',
                width: 150,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge tone={statusTone(row.status)} size="sm">
                        {String(value).replaceAll('_', ' ')}
                    </Badge>
                ),
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
                        ]}
                    />
                ),
            },
        ],
        [onView],
    );

    return (
        <section aria-labelledby="loans-heading">
            <div className={styles.sectionHead}>
                <div>
                    <h2 id="loans-heading" className={styles.sectionTitle}>
                        Loans
                    </h2>
                    <p className={styles.count}>
                        {filtersActive ? `${totalFiltered} of ${totalAll} loans` : `${totalAll} loans`}
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
                        icon={<HandCoins size={24} />}
                        title="No loans match these filters"
                        description="Try a shorter search or clear the filters to see the full loan book."
                        action={
                            <Button variant="secondary" onClick={onClearFilters}>
                                Clear filters
                            </Button>
                        }
                    />
                ) : (
                    <Skeleton label="Loading loans" lines={6} loading={loading}>
                        <SortableDataTable
                            rows={rows}
                            columns={columns}
                            rowKey="id"
                            caption="Loans"
                            emptyMessage="No loans to show"
                            defaultSort={{ key: 'loan_no', direction: 'desc' }}
                            itemName={{ one: 'loan', other: 'loans' }}
                        />
                    </Skeleton>
                )}
            </div>
        </section>
    );
}
