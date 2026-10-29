import { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { DropdownMenu } from '@/components/arc/dropdown-menu/dropdown-menu';
import { EmptyState } from '@/components/arc/empty-state/empty-state';
import { Skeleton } from '@/components/arc/skeleton/skeleton';
import {
    SortableDataTable,
    type DataColumn,
    type SortState,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import {
    Ban,
    CircleCheck,
    Ellipsis,
    Eye,
    SearchX,
    Send,
    Trash,
    Undo2,
} from 'lucide-react';
import { formatDate, transactionUrl } from '@/lib/transactions';
import { formatNGN } from '@/lib/format';
import type { JournalTransactionRow } from '@/types/ledger';
import { TransactionStatusBadge } from './TransactionStatusBadge';
import styles from './transactions.module.css';

interface TransactionsTableProps {
    rows: JournalTransactionRow[];
    countLabel: string;
    filtersActive: boolean;
    onClearFilters: () => void;
    loading: boolean;
    sort: SortState;
    onSortChange: (sort: SortState) => void;
    sortActive: boolean;
    onResetSort: () => void;
    onView: (row: JournalTransactionRow) => void;
    onSubmit: (row: JournalTransactionRow) => void;
    onCancel: (row: JournalTransactionRow) => void;
    onPost: (row: JournalTransactionRow) => void;
    onReverse: (row: JournalTransactionRow) => void;
    onDelete: (row: JournalTransactionRow) => void;
}

export function TransactionsTable({
    rows,
    countLabel,
    filtersActive,
    onClearFilters,
    loading,
    sort,
    onSortChange,
    sortActive,
    onResetSort,
    onView,
    onSubmit,
    onCancel,
    onPost,
    onReverse,
    onDelete,
}: TransactionsTableProps) {
    const columns = useMemo<DataColumn<JournalTransactionRow>[]>(
        () => [
            {
                key: 'ref',
                label: 'Reference',
                width: 130,
                sortable: false,
                render: (value, row) => (
                    <Link
                        href={transactionUrl(row.id)}
                        className={styles.refLink}
                    >
                        {String(value)}
                    </Link>
                ),
            },
            {
                key: 'description',
                label: 'Description',
                sortable: false,
            },
            {
                key: 'effective_date',
                label: 'Effective date',
                render: (value) => formatDate(String(value)),
            },
            {
                key: 'status',
                label: 'Status',
                sortable: false,
                render: (value, row) => (
                    <TransactionStatusBadge status={row.status} />
                ),
            },
            {
                key: 'entries_count',
                label: 'Entries',
                width: 84,
                sortable: false,
            },
            {
                key: 'debit_total',
                label: 'Debits',
                width: 150,
                sortable: false,
                render: (value, row) => formatNGN(Number(row.debit_total)),
            },
            {
                key: 'credit_total',
                label: 'Credits',
                width: 150,
                sortable: false,
                render: (value, row) => formatNGN(Number(row.credit_total)),
            },
            {
                key: 'id',
                label: 'Actions',
                sortable: false,
                render: (_value, row) => (
                    <DropdownMenu
                        label={`Actions for ${row.ref}`}
                        icon={<Ellipsis size={15} aria-hidden="true" />}
                        items={[
                            {
                                label: 'View',
                                icon: <Eye size={15} aria-hidden="true" />,
                                onSelect: () => onView(row),
                            },
                            ...(row.status === 'DRAFT'
                                ? [
                                      {
                                          label: 'Submit',
                                          icon: (
                                              <Send
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          onSelect: () => onSubmit(row),
                                      },
                                  ]
                                : []),
                            ...(row.status === 'SUBMITTED'
                                ? [
                                      {
                                          label: 'Post',
                                          icon: (
                                              <CircleCheck
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          onSelect: () => onPost(row),
                                      },
                                  ]
                                : []),
                            ...(row.status === 'POSTED'
                                ? [
                                      {
                                          label: 'Reverse',
                                          icon: (
                                              <Undo2
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          onSelect: () => onReverse(row),
                                      },
                                  ]
                                : []),
                            ...(row.status === 'DRAFT' ||
                            row.status === 'SUBMITTED'
                                ? [
                                      {
                                          label: 'Cancel',
                                          icon: (
                                              <Ban
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          onSelect: () => onCancel(row),
                                      },
                                  ]
                                : []),
                            ...(row.status === 'DRAFT'
                                ? [
                                      {
                                          label: 'Delete',
                                          icon: (
                                              <Trash
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          destructive: true,
                                          separatorBefore: true,
                                          onSelect: () => onDelete(row),
                                      },
                                  ]
                                : []),
                        ]}
                    />
                ),
            },
        ],
        [onView, onSubmit, onCancel, onPost, onReverse, onDelete],
    );

    return (
        <section aria-labelledby="transactions-heading">
            <div className={styles.sectionHead}>
                <div>
                    <h2
                        id="transactions-heading"
                        className={styles.sectionTitle}
                    >
                        Transactions
                    </h2>
                    <p className={styles.count}>{countLabel}</p>
                </div>
                <div className={styles.sectionActions}>
                    {sortActive ? (
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={onResetSort}
                        >
                            Reset sort
                        </Button>
                    ) : null}
                    {filtersActive ? (
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={onClearFilters}
                        >
                            Clear filters
                        </Button>
                    ) : null}
                </div>
            </div>

            <div className={styles.tableWrap}>
                {rows.length === 0 && !loading ? (
                    <EmptyState
                        icon={<SearchX size={24} />}
                        title="No transactions match these filters"
                        description="Try a shorter search or clear the filters to see every transaction."
                        action={
                            <Button
                                variant="secondary"
                                onClick={onClearFilters}
                            >
                                Clear filters
                            </Button>
                        }
                    />
                ) : (
                    <Skeleton
                        label="Loading transactions"
                        lines={6}
                        loading={loading}
                    >
                        <SortableDataTable
                            key={`${sort.key}-${sort.direction}`}
                            rows={rows}
                            columns={columns}
                            rowKey="id"
                            caption="Journal transactions"
                            emptyMessage="No transactions to show"
                            defaultSort={sort}
                            onSortChange={onSortChange}
                            itemName={{
                                one: 'transaction',
                                other: 'transactions',
                            }}
                        />
                    </Skeleton>
                )}
            </div>
        </section>
    );
}
