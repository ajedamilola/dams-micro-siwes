import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import { AppLayout } from '@/components/layout/AppLayout';
import { accountUrl } from '@/lib/accounts';
import type { AccountStatus, ChartAccount } from '@/types/account';
import type { LedgerOption } from '@/types/ledger';
import {
    AccountToolbar,
    type LedgerFilter,
    type TypeFilter,
    type StatusFilter,
} from './components/AccountToolbar';
import { AccountFormModal } from './components/AccountFormModal';
import { AccountsTable, type AccountRow } from './components/AccountsTable';
import styles from './components/accounts.module.css';

interface AccountsIndexProps {
    accounts: ChartAccount[];
    ledgers: LedgerOption[];
}

/** The master chart is the ledger no control account points to as a subledger. */
function masterLedgerId(
    ledgers: LedgerOption[],
    accounts: ChartAccount[],
): string {
    const subledgerIds = new Set(
        accounts
            .map((account) => account.subledger_id)
            .filter((id): id is string => id !== null),
    );
    return (
        ledgers.find((ledger) => !subledgerIds.has(ledger.id))?.id ??
        ledgers[0]?.id ??
        'all'
    );
}

function toCSV(rows: AccountRow[]): string {
    const header = [
        'Ledger',
        'Code',
        'Account name',
        'Type',
        'Normal balance',
        'Status',
        'Balance (NGN)',
    ];
    const escape = (value: string | number): string => {
        const text = String(value);
        return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
    };
    const lines = rows.map((row) =>
        [
            row.ledger_code ?? '',
            row.code,
            row.name,
            row.type,
            row.balance_type,
            row.status,
            row.balance.toFixed(2),
        ]
            .map(escape)
            .join(','),
    );
    return [header.join(','), ...lines].join('\n');
}

export default function AccountsIndex({
    accounts,
    ledgers,
}: AccountsIndexProps) {
    const [query, setQuery] = useState('');
    const [typeFilter, setTypeFilter] = useState<TypeFilter>('all');
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
    const masterLedger = masterLedgerId(ledgers, accounts);
    const [ledgerFilter, setLedgerFilter] =
        useState<LedgerFilter>(masterLedger);
    const [collapsedIds, setCollapsedIds] = useState<string[]>([]);
    const [modal, setModal] = useState<
        { mode: 'create' } | { mode: 'edit'; id: string } | null
    >(null);
    const [deleting, setDeleting] = useState<AccountRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | null>(null);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const rows = useMemo<AccountRow[]>(
        () =>
            accounts.map((account) => ({
                ...account,
                balance: Number(account.account_balance),
                actions: account.id,
            })),
        [accounts],
    );

    const groupIds = useMemo(
        () => rows.filter((row) => row.is_group).map((row) => row.id),
        [rows],
    );

    const visibleRows = useMemo(() => {
        const collapsed = new Set(collapsedIds);
        const parents = new Map(rows.map((row) => [row.id, row.parent_id]));
        return rows.filter((row) => {
            let parent = parents.get(row.id);
            while (parent) {
                if (collapsed.has(parent)) {
                    return false;
                }
                parent = parents.get(parent) ?? null;
            }
            return true;
        });
    }, [rows, collapsedIds]);

    const filteredRows = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return visibleRows.filter((row) => {
            if (ledgerFilter !== 'all' && row.ledger_id !== ledgerFilter) {
                return false;
            }
            if (typeFilter !== 'all' && row.type !== typeFilter) {
                return false;
            }
            if (statusFilter !== 'all' && row.status !== statusFilter) {
                return false;
            }
            if (needle === '') {
                return true;
            }
            return (
                row.code.toLowerCase().includes(needle) ||
                row.name.toLowerCase().includes(needle) ||
                (row.description ?? '').toLowerCase().includes(needle)
            );
        });
    }, [visibleRows, query, ledgerFilter, typeFilter, statusFilter]);

    const ledgerTotal = useMemo(
        () =>
            ledgerFilter === 'all'
                ? rows.length
                : rows.filter((row) => row.ledger_id === ledgerFilter).length,
        [rows, ledgerFilter],
    );

    const filtersActive =
        query.trim() !== '' ||
        ledgerFilter !== 'all' ||
        typeFilter !== 'all' ||
        statusFilter !== 'all';

    function clearFilters(): void {
        setQuery('');
        setLedgerFilter(masterLedger);
        setTypeFilter('all');
        setStatusFilter('all');
    }

    function toggleCollapse(id: string): void {
        setCollapsedIds((current) =>
            current.includes(id)
                ? current.filter((entry) => entry !== id)
                : [...current, id],
        );
    }

    function toggleStatus(row: AccountRow): void {
        const next: AccountStatus =
            row.status === 'active' ? 'inactive' : 'active';
        router.put(accountUrl(row.id, row.ledger_id), { status: next });
    }

    function confirmDelete(row: AccountRow): void {
        setDeleteError(null);
        setDeleting(row);
    }

    function deleteAccount(): void {
        if (!deleting) {
            return;
        }
        router.delete(accountUrl(deleting.id, deleting.ledger_id), {
            onSuccess: () => setDeleting(null),
            onError: (errors) =>
                setDeleteError(
                    Object.values(errors)[0] ??
                        'This account could not be deleted.',
                ),
        });
    }

    function exportCSV(): void {
        const blob = new Blob([toCSV(filteredRows)], {
            type: 'text/csv;charset=utf-8',
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'chart-of-accounts.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    return (
        <>
            <Head title="Chart of accounts" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>Chart of accounts</h1>
                        <p className={styles.description}>
                            Every ledger account in one place, grouped by type
                            and hierarchy. Balances are in naira and group
                            totals roll up from their sub-accounts.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button
                            variant="secondary"
                            onClick={exportCSV}
                            disabled={filteredRows.length === 0}
                        >
                            Export CSV
                        </Button>
                        <Button onClick={() => setModal({ mode: 'create' })}>
                            New account
                        </Button>
                    </div>
                </div>

                <AccountToolbar
                    query={query}
                    onQueryChange={setQuery}
                    typeFilter={typeFilter}
                    onTypeChange={setTypeFilter}
                    statusFilter={statusFilter}
                    onStatusChange={setStatusFilter}
                    ledgers={ledgers}
                    ledgerFilter={ledgerFilter}
                    onLedgerChange={setLedgerFilter}
                />

                <AccountsTable
                    rows={filteredRows}
                    totalFiltered={filteredRows.length}
                    totalAll={ledgerTotal}
                    collapsedIds={collapsedIds}
                    onToggleCollapse={toggleCollapse}
                    onExpandAll={() => setCollapsedIds([])}
                    onCollapseAll={() => setCollapsedIds(groupIds)}
                    onToggleStatus={(id) => {
                        const row = rows.find((entry) => entry.id === id);
                        if (row) {
                            toggleStatus(row);
                        }
                    }}
                    onView={(row) =>
                        router.visit(accountUrl(row.id, row.ledger_id))
                    }
                    onEdit={(row) => setModal({ mode: 'edit', id: row.id })}
                    onDelete={confirmDelete}
                    filtersActive={filtersActive}
                    onClearFilters={clearFilters}
                    loading={!mounted}
                    showLedger={ledgerFilter === 'all'}
                />

                {modal ? (
                    <AccountFormModal
                        key={
                            modal.mode === 'edit'
                                ? `edit-${modal.id}`
                                : 'create'
                        }
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setModal(null);
                            }
                        }}
                        mode={modal.mode}
                        initial={
                            modal.mode === 'edit'
                                ? (rows.find((row) => row.id === modal.id) ??
                                  null)
                                : null
                        }
                        rows={rows.map((row) => ({
                            id: row.id,
                            code: row.code,
                            name: row.name,
                            parent_id: row.parent_id,
                            is_group: row.is_group,
                            ledger_id: row.ledger_id,
                        }))}
                        ledgers={ledgers}
                        defaultLedgerId={
                            ledgerFilter === 'all'
                                ? masterLedger === 'all'
                                    ? ''
                                    : masterLedger
                                : ledgerFilter
                        }
                    />
                ) : null}

                {deleting ? (
                    <Dialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setDeleting(null);
                            }
                        }}
                    >
                        <DialogContent
                            title={`Delete ${deleting.code} ${deleting.name}?`}
                            description="This removes the account for good. Accounts with sub-accounts or journal entries cannot be deleted."
                        >
                            {deleteError ? (
                                <p className={styles.formError} role="alert">
                                    {deleteError}
                                </p>
                            ) : null}
                            <div className={styles.formActions}>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="button"
                                    variant="danger"
                                    onClick={deleteAccount}
                                >
                                    Delete account
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                ) : null}
            </main>
        </>
    );
}

// Persistent layout: the sidebar and top bar stay mounted across visits.
(
    AccountsIndex as unknown as { layout: (page: ReactNode) => ReactNode }
).layout = (page) => (
    <AppLayout title="Chart of accounts" section="General ledger">
        {page}
    </AppLayout>
);
