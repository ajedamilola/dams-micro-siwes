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
import { Ellipsis, Eye, PackageSearch, Pencil } from 'lucide-react';
import { formatNGN } from '@/lib/format';
import { humanizeToken } from '@/lib/loan-customers';
import type { LoanProduct } from '@/types/loan';
import styles from './products.module.css';

export type ProductRow = LoanProduct & Record<string, unknown>;

const METHOD_TONE: Record<string, 'info' | 'warning'> = {
    flat: 'info',
    reducing: 'warning',
};

const STATUS_TONE: Record<string, 'success' | 'neutral'> = {
    active: 'success',
    inactive: 'neutral',
};

function ProductName({ row }: { row: ProductRow }) {
    return (
        <span className={styles.nameCell}>
            <span className={styles.nameMain}>{row.name}</span>
            <span className={styles.nameSub}>
                {row.code} · {humanizeToken(row.repayment_frequency)} · {row.annual_interest_rate}% p.a.
            </span>
        </span>
    );
}

interface ProductsTableProps {
    rows: ProductRow[];
    totalFiltered: number;
    totalAll: number;
    onView: (row: ProductRow) => void;
    onEdit: (row: ProductRow) => void;
    filtersActive: boolean;
    onClearFilters: () => void;
    loading: boolean;
}

export function ProductsTable({
    rows,
    totalFiltered,
    totalAll,
    onView,
    onEdit,
    filtersActive,
    onClearFilters,
    loading,
}: ProductsTableProps) {
    const columns = useMemo<DataColumn<ProductRow>[]>(
        () => [
            { key: 'code', label: 'Code', width: 110 },
            {
                key: 'name',
                label: 'Product',
                sortable: false,
                render: (_value, row) => <ProductName row={row} />,
            },
            {
                key: 'interest_method',
                label: 'Method',
                render: (value, row) => (
                    <Badge tone={METHOD_TONE[row.interest_method] ?? 'info'} size="sm">
                        {humanizeToken(String(value))}
                    </Badge>
                ),
            },
            {
                key: 'min_principal',
                label: 'Principal range',
                width: 220,
                render: (_value, row) => (
                    <span className={styles.mono}>
                        {formatNGN(Number(row.min_principal))} – {formatNGN(Number(row.max_principal))}
                    </span>
                ),
            },
            {
                key: 'min_tenor',
                label: 'Tenor',
                width: 110,
                render: (_value, row) => (
                    <span className={styles.mono}>
                        {row.min_tenor}–{row.max_tenor}
                    </span>
                ),
            },
            {
                key: 'loans_count',
                label: 'Loans',
                width: 90,
                render: (value) => <span className={styles.mono}>{String(value)}</span>,
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge tone={STATUS_TONE[row.status] ?? 'neutral'} size="sm">
                        {String(value)}
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
                            {
                                label: 'Edit',
                                icon: <Pencil size={15} aria-hidden="true" />,
                                onSelect: () => onEdit(row),
                            },
                        ]}
                    />
                ),
            },
        ],
        [onView, onEdit],
    );

    return (
        <section aria-labelledby="products-heading">
            <div className={styles.sectionHead}>
                <div>
                    <h2 id="products-heading" className={styles.sectionTitle}>
                        Products
                    </h2>
                    <p className={styles.count}>
                        {filtersActive
                            ? `${totalFiltered} of ${totalAll} products`
                            : `${totalAll} products`}
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
                        icon={<PackageSearch size={24} />}
                        title="No products match these filters"
                        description="Try a shorter search or clear the filters to see the full product list."
                        action={
                            <Button variant="secondary" onClick={onClearFilters}>
                                Clear filters
                            </Button>
                        }
                    />
                ) : (
                    <Skeleton label="Loading products" lines={6} loading={loading}>
                        <SortableDataTable
                            rows={rows}
                            columns={columns}
                            rowKey="id"
                            caption="Loan products"
                            emptyMessage="No products to show"
                            defaultSort={{ key: 'code', direction: 'asc' }}
                            itemName={{ one: 'product', other: 'products' }}
                        />
                    </Skeleton>
                )}
            </div>
        </section>
    );
}
