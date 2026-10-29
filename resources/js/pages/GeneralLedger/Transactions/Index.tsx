import { useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { MetricCard } from '@/components/arc/metric-card/metric-card';
import { SearchField } from '@/components/arc/search-field/search-field';
import { Select } from '@/components/arc/select/select';
import { AppLayout } from '@/components/layout/AppLayout';
import { transactionUrl } from '@/lib/transactions';
import type { SortState } from '@/components/arc/sortable-data-table/sortable-data-table';
import type {
    JournalTransactionRow,
    SortDirection,
    TransactionSortKey,
    TransactionStatus,
} from '@/types/ledger';
import { ConfirmDialog } from './components/ConfirmDialog';
import { TransactionFormModal } from './components/TransactionFormModal';
import { TransactionsTable } from './components/TransactionsTable';
import styles from './components/transactions.module.css';

interface TransactionPage {
    data: JournalTransactionRow[];
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface TransactionsIndexProps {
    transactions: TransactionPage;
    stats: { total: number; draft: number; submitted: number; posted: number };
    filters: {
        q: string;
        status: StatusFilter;
        sort: TransactionSortKey;
        direction: SortDirection;
    };
}

type StatusFilter = TransactionStatus | 'all';

const STATUS_OPTIONS = [
    { value: 'all', label: 'All statuses' },
    { value: 'DRAFT', label: 'Draft' },
    { value: 'SUBMITTED', label: 'Submitted' },
    { value: 'POSTED', label: 'Posted' },
    { value: 'REVERSED', label: 'Reversed' },
    { value: 'CANCELLED', label: 'Cancelled' },
];

type PendingAction =
    | {
          kind: 'submit' | 'cancel' | 'post' | 'reverse' | 'delete';
          row: JournalTransactionRow;
      }
    | null;

const ACTION_COPY = {
    submit: {
        title: 'Submit this transaction?',
        description:
            'Submitting locks the entries and moves the transaction out of draft. Only balanced transactions can be submitted.',
        confirmLabel: 'Submit transaction',
        danger: false,
    },
    cancel: {
        title: 'Cancel this transaction?',
        description:
            'Cancelled transactions are kept for audit but can no longer be edited or posted.',
        confirmLabel: 'Cancel transaction',
        danger: true,
    },
    post: {
        title: 'Post this transaction?',
        description:
            'Posting writes the entries to the ledger balances. Posted transactions can only be reversed.',
        confirmLabel: 'Post transaction',
        danger: false,
    },
    reverse: {
        title: 'Reverse this transaction?',
        description:
            'A mirrored transaction is booked and posted, netting every balance back. The original is kept as reversed.',
        confirmLabel: 'Reverse transaction',
        danger: true,
    },
    delete: {
        title: 'Delete this draft?',
        description:
            'This removes the transaction and its entries for good. Only drafts can be deleted.',
        confirmLabel: 'Delete transaction',
        danger: true,
    },
} as const;

function searchParams(
    query: string,
    status: StatusFilter,
    sort: SortState,
): Record<string, string> {
    const params: Record<string, string> = {};
    if (query.trim() !== '') {
        params.q = query.trim();
    }
    if (status !== 'all') {
        params.status = status;
    }
    if (sort.key !== 'created_at' || sort.direction !== 'desc') {
        params.sort = sort.key;
        params.direction = sort.direction;
    }
    return params;
}

export default function TransactionsIndex({
    transactions,
    stats,
    filters,
}: TransactionsIndexProps) {
    const [query, setQuery] = useState(filters.q);
    const [statusFilter, setStatusFilter] =
        useState<StatusFilter>(filters.status);
    const [creating, setCreating] = useState(false);
    const [pending, setPending] = useState<PendingAction>(null);
    const [actionError, setActionError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [mounted, setMounted] = useState(false);
    const firstRender = useRef(true);
    const lastSent = useRef('');

    useEffect(() => {
        setMounted(true);
    }, []);

    function navigate(params: Record<string, string>): void {
        const key = JSON.stringify(params);
        if (key === lastSent.current) {
            return;
        }
        lastSent.current = key;
        router.get('/general-ledger/transactions', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    // Keep the fields in step when the server state changes elsewhere.
    useEffect(() => {
        setQuery(filters.q);
        setStatusFilter(filters.status);
    }, [filters.q, filters.status]);

    // Debounced server search: filters run in the backend, latest first.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = window.setTimeout(() => {
            navigate(
                searchParams(query, statusFilter, {
                    key: filters.sort,
                    direction: filters.direction,
                }),
            );
        }, 400);
        return () => window.clearTimeout(timer);
    }, [query, statusFilter, filters.sort, filters.direction]);

    const rows = useMemo(() => transactions.data, [transactions.data]);
    const filtersActive = filters.q !== '' || filters.status !== 'all';
    const sort: SortState = useMemo(
        () => ({ key: filters.sort, direction: filters.direction }),
        [filters.sort, filters.direction],
    );
    const sortActive = sort.key !== 'created_at' || sort.direction !== 'desc';

    function clearFilters(): void {
        setQuery('');
        setStatusFilter('all');
        navigate(searchParams('', 'all', sort));
    }

    function handleSortChange(next: SortState): void {
        // Only the effective date column is sortable; anything else is
        // ignored rather than sent to the backend.
        if (next.key !== 'effective_date') {
            return;
        }
        navigate(searchParams(query, statusFilter, next));
    }

    function resetSort(): void {
        navigate(
            searchParams(query, statusFilter, {
                key: 'created_at',
                direction: 'desc',
            }),
        );
    }

    function visit(url: string | null): void {
        if (url) {
            router.visit(url, { preserveScroll: true });
        }
    }

    function confirmPending(): void {
        if (!pending) {
            return;
        }
        setActionError(null);
        setProcessing(true);
        const done = () => {
            setProcessing(false);
            setPending(null);
        };
        const onError = (errors: Record<string, string>) =>
            setActionError(
                Object.values(errors)[0] ?? 'This action could not run.',
            );
        if (pending.kind === 'delete') {
            router.delete(`/general-ledger/transactions/${pending.row.id}`, {
                onSuccess: done,
                onError: (errors) => {
                    setProcessing(false);
                    onError(errors);
                },
            });
            return;
        }
        if (pending.kind === 'post' || pending.kind === 'reverse') {
            router.post(
                `/general-ledger/transactions/${pending.row.id}/${pending.kind}`,
                undefined,
                {
                    onSuccess: done,
                    onError: (errors) => {
                        setProcessing(false);
                        onError(errors);
                    },
                },
            );
            return;
        }
        router.put(
            `/general-ledger/transactions/${pending.row.id}`,
            {
                status:
                    pending.kind === 'submit' ? 'SUBMITTED' : 'CANCELLED',
            },
            {
                onSuccess: done,
                onError: (errors) => {
                    setProcessing(false);
                    onError(errors);
                },
            },
        );
    }

    return (
        <>
            <Head title="Transactions" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>Transactions</h1>
                        <p className={styles.description}>
                            Drafts are edited with their journal entries, then
                            submitted for posting. Cancelling keeps the audit
                            trail without posting anything.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button onClick={() => setCreating(true)}>
                            New transaction
                        </Button>
                    </div>
                </div>

                <div className={styles.stats} role="group" aria-label="Transaction stats">
                    <MetricCard
                        label="Total"
                        value={stats.total}
                        context="All journal transactions"
                    />
                    <MetricCard
                        label="Draft"
                        value={stats.draft}
                        context="Editable, not yet submitted"
                    />
                    <MetricCard
                        label="Submitted"
                        value={stats.submitted}
                        context="Balanced, awaiting posting"
                    />
                    <MetricCard
                        label="Posted"
                        value={stats.posted}
                        context="Posted to the ledger"
                    />
                </div>

                <div
                    className={styles.toolbar}
                    role="group"
                    aria-label="Filter transactions"
                >
                    <div className={styles.search}>
                        <SearchField
                            label="Search transactions"
                            placeholder="Search by reference or description"
                            value={query}
                            onValueChange={setQuery}
                        />
                    </div>
                    <div className={styles.filter}>
                        <Select
                            label="Status"
                            value={statusFilter}
                            onValueChange={(value) =>
                                setStatusFilter(value as StatusFilter)
                            }
                            options={STATUS_OPTIONS}
                        />
                    </div>
                </div>

                <TransactionsTable
                    rows={rows}
                    countLabel={
                        filtersActive
                            ? `${rows.length} shown on this page`
                            : `${stats.total} transactions`
                    }
                    filtersActive={filtersActive}
                    onClearFilters={clearFilters}
                    loading={!mounted}
                    sort={sort}
                    onSortChange={handleSortChange}
                    sortActive={sortActive}
                    onResetSort={resetSort}
                    onView={(row) => router.visit(transactionUrl(row.id))}
                    onSubmit={(row) => {
                        setActionError(null);
                        setPending({ kind: 'submit', row });
                    }}
                    onCancel={(row) => {
                        setActionError(null);
                        setPending({ kind: 'cancel', row });
                    }}
                    onPost={(row) => {
                        setActionError(null);
                        setPending({ kind: 'post', row });
                    }}
                    onReverse={(row) => {
                        setActionError(null);
                        setPending({ kind: 'reverse', row });
                    }}
                    onDelete={(row) => {
                        setActionError(null);
                        setPending({ kind: 'delete', row });
                    }}
                />

                <div className={styles.pager} role="group" aria-label="Pages">
                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={!transactions.prev_page_url}
                        onClick={() => visit(transactions.prev_page_url)}
                    >
                        Previous
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        disabled={!transactions.next_page_url}
                        onClick={() => visit(transactions.next_page_url)}
                    >
                        Next
                    </Button>
                </div>

                {creating ? (
                    <TransactionFormModal
                        key="create-transaction"
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setCreating(false);
                            }
                        }}
                        mode="create"
                    />
                ) : null}

                {pending ? (
                    <ConfirmDialog
                        key={`${pending.kind}-${pending.row.ref}`}
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setPending(null);
                            }
                        }}
                        title={`${ACTION_COPY[pending.kind].title.replace('this transaction', pending.row.ref)}`}
                        description={ACTION_COPY[pending.kind].description}
                        confirmLabel={ACTION_COPY[pending.kind].confirmLabel}
                        danger={ACTION_COPY[pending.kind].danger}
                        processing={processing}
                        error={actionError}
                        onConfirm={confirmPending}
                    />
                ) : null}
            </main>
        </>
    );
}

(
    TransactionsIndex as unknown as { layout: (page: ReactNode) => ReactNode }
).layout = (page) => (
    <AppLayout title="Transactions" section="General ledger">
        {page}
    </AppLayout>
);
