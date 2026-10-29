import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { EmptyState } from '@/components/arc/empty-state/empty-state';
import { Skeleton } from '@/components/arc/skeleton/skeleton';
import { ListPlus } from 'lucide-react';
import { AppLayout } from '@/components/layout/AppLayout';
import { formatNGN } from '@/lib/format';
import {
    entryTotals,
    entryUrl,
    formatDate,
    formatDateTime,
    isBalanced,
    transactionUrl,
} from '@/lib/transactions';
import type {
    JournalEntryRow,
    JournalTransactionRow,
} from '@/types/ledger';
import { ConfirmDialog } from './components/ConfirmDialog';
import { EntriesTable } from './components/EntriesTable';
import { EntryFormModal } from './components/EntryFormModal';
import { TransactionFormModal } from './components/TransactionFormModal';
import { TransactionStatusBadge } from './components/TransactionStatusBadge';
import styles from './components/transactions.module.css';

interface TransactionLink {
    id: string;
    ref: string;
}

interface TransactionShowProps {
    transaction: JournalTransactionRow;
    permissions: {
        editable: boolean;
        can_submit: boolean;
        can_cancel: boolean;
        can_post: boolean;
        can_reverse: boolean;
    };
    reverses: TransactionLink | null;
    reversed_by: TransactionLink | null;
}

type EntryModal =
    | { mode: 'create' }
    | { mode: 'edit'; entry: JournalEntryRow }
    | null;

export default function TransactionShow({
    transaction,
    permissions,
    reverses,
    reversed_by,
}: TransactionShowProps) {
    const [entryModal, setEntryModal] = useState<EntryModal>(null);
    const [editing, setEditing] = useState(false);
    const [confirming, setConfirming] = useState<
        | 'submit'
        | 'cancel'
        | 'post'
        | 'reverse'
        | 'delete-transaction'
        | 'delete-entry'
        | null
    >(null);
    const [entryTarget, setEntryTarget] = useState<JournalEntryRow | null>(
        null,
    );
    const [actionError, setActionError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const { entries } = transaction;
    const { debits, credits } = entryTotals(entries);
    const balanced = isBalanced(entries);

    function runStatus(status: 'SUBMITTED' | 'CANCELLED'): void {
        setActionError(null);
        setProcessing(true);
        router.put(
            `/general-ledger/transactions/${transaction.id}`,
            { status },
            {
                onSuccess: () => {
                    setProcessing(false);
                    setConfirming(null);
                },
                onError: (errors) => {
                    setProcessing(false);
                    setActionError(
                        Object.values(errors)[0] ??
                            'This action could not run.',
                    );
                },
            },
        );
    }

    function postTransaction(): void {
        setActionError(null);
        setProcessing(true);
        router.post(`/general-ledger/transactions/${transaction.id}/post`, undefined, {
            onSuccess: () => {
                setProcessing(false);
                setConfirming(null);
            },
            onError: (errors) => {
                setProcessing(false);
                setActionError(
                    Object.values(errors)[0] ??
                        'This transaction could not be posted.',
                );
            },
        });
    }

    function reverseTransaction(): void {
        setActionError(null);
        setProcessing(true);
        router.post(
            `/general-ledger/transactions/${transaction.id}/reverse`,
            undefined,
            {
                onSuccess: () => {
                    setProcessing(false);
                    setConfirming(null);
                },
                onError: (errors) => {
                    setProcessing(false);
                    setActionError(
                        Object.values(errors)[0] ??
                            'This transaction could not be reversed.',
                    );
                },
            },
        );
    }

    function deleteTransaction(): void {
        setActionError(null);
        setProcessing(true);
        router.delete(`/general-ledger/transactions/${transaction.id}`, {
            onError: (errors) => {
                setProcessing(false);
                setActionError(
                    Object.values(errors)[0] ?? 'This could not be deleted.',
                );
            },
        });
    }

    function deleteEntry(): void {
        if (!entryTarget) {
            return;
        }
        setActionError(null);
        setProcessing(true);
        router.delete(entryUrl(transaction.id, entryTarget.id), {
            onSuccess: () => {
                setProcessing(false);
                setConfirming(null);
                setEntryTarget(null);
            },
            onError: (errors) => {
                setProcessing(false);
                setActionError(
                    Object.values(errors)[0] ?? 'This entry could not be deleted.',
                );
            },
        });
    }

    return (
        <>
            <Head title={transaction.ref} />
            <main className={styles.page}>
                <nav className={styles.crumbs} aria-label="Breadcrumb">
                    <ol>
                        <li>
                            <Link
                                href="/general-ledger/transactions"
                                className={styles.crumbLink}
                            >
                                Transactions
                            </Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{transaction.ref}</li>
                    </ol>
                </nav>

                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>{transaction.ref}</h1>
                        <p className={styles.description}>
                            {transaction.description || 'No description yet.'}
                        </p>
                        <div className={styles.badges}>
                            <TransactionStatusBadge
                                status={transaction.status}
                            />
                            <Badge
                                tone={balanced ? 'success' : 'danger'}
                                size="sm"
                            >
                                {balanced ? 'Balanced' : 'Out of balance'}
                            </Badge>
                        </div>
                    </div>
                    <div className={styles.actions}>
                        {permissions.editable ? (
                            <>
                                <Button
                                    variant="secondary"
                                    onClick={() => setEditing(true)}
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        setActionError(null);
                                        setConfirming('delete-transaction');
                                    }}
                                >
                                    Delete
                                </Button>
                            </>
                        ) : null}
                        {permissions.can_cancel ? (
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setActionError(null);
                                    setConfirming('cancel');
                                }}
                            >
                                Cancel
                            </Button>
                        ) : null}
                        {permissions.can_submit ? (
                            <Button
                                onClick={() => {
                                    setActionError(null);
                                    setConfirming('submit');
                                }}
                                disabled={!balanced}
                                title={
                                    balanced
                                        ? undefined
                                        : 'Debits must equal credits before submitting'
                                }
                            >
                                Submit
                            </Button>
                        ) : null}
                        {permissions.can_post ? (
                            <Button
                                onClick={() => {
                                    setActionError(null);
                                    setConfirming('post');
                                }}
                            >
                                Post
                            </Button>
                        ) : null}
                        {permissions.can_reverse ? (
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setActionError(null);
                                    setConfirming('reverse');
                                }}
                            >
                                Reverse
                            </Button>
                        ) : null}
                        {permissions.editable ? (
                            <Button
                                onClick={() =>
                                    setEntryModal({ mode: 'create' })
                                }
                            >
                                Add entry
                            </Button>
                        ) : null}
                    </div>
                </div>

                <section aria-label="Entry totals">
                    <div className={styles.balanceBar}>
                        <div className={styles.balanceCell}>
                            <p className={styles.balanceLabel}>Total debits</p>
                            <p className={styles.balanceValue}>
                                {formatNGN(debits)}
                            </p>
                        </div>
                        <div className={styles.balanceCell}>
                            <p className={styles.balanceLabel}>Total credits</p>
                            <p className={styles.balanceValue}>
                                {formatNGN(credits)}
                            </p>
                        </div>
                        <div className={styles.balanceCell}>
                            <p className={styles.balanceLabel}>Difference</p>
                            <p
                                className={`${styles.balanceValue} ${Math.abs(debits - credits) >= 0.005 ? styles.negative : ''}`}
                            >
                                {formatNGN(debits - credits)}
                            </p>
                        </div>
                    </div>
                    {!balanced && permissions.editable ? (
                        <p className={styles.hint}>
                            Add matching debits and credits. A transaction must
                            balance before it can be submitted.
                        </p>
                    ) : null}
                </section>

                <section aria-label="Transaction facts">
                    <dl className={styles.facts}>
                        <div className={styles.fact}>
                            <dt>Effective date</dt>
                            <dd>
                                {formatDate(transaction.effective_date)}
                            </dd>
                        </div>
                        <div className={styles.fact}>
                            <dt>Posted</dt>
                            <dd>
                                {transaction.posted_at
                                    ? formatDateTime(transaction.posted_at)
                                    : 'Not posted'}
                            </dd>
                        </div>
                        <div className={styles.fact}>
                            <dt>Entries</dt>
                            <dd>
                                {entries.length}{' '}
                                {entries.length === 1 ? 'entry' : 'entries'}
                            </dd>
                        </div>
                        {reverses ? (
                            <div className={styles.fact}>
                                <dt>Reverses</dt>
                                <dd>
                                    <Link
                                        href={transactionUrl(reverses.id)}
                                        className={styles.nameLink}
                                    >
                                        {reverses.ref}
                                    </Link>
                                </dd>
                            </div>
                        ) : null}
                        {reversed_by ? (
                            <div className={styles.fact}>
                                <dt>Reversed by</dt>
                                <dd>
                                    <Link
                                        href={transactionUrl(reversed_by.id)}
                                        className={styles.nameLink}
                                    >
                                        {reversed_by.ref}
                                    </Link>
                                </dd>
                            </div>
                        ) : null}
                    </dl>
                </section>

                <section aria-labelledby="entries-heading">
                    <div className={styles.sectionHead}>
                        <div>
                            <h2
                                id="entries-heading"
                                className={styles.sectionTitle}
                            >
                                Journal entries
                            </h2>
                            <p className={styles.count}>
                                {entries.length}{' '}
                                {entries.length === 1 ? 'entry' : 'entries'}
                            </p>
                        </div>
                    </div>

                    <div className={styles.tableWrap}>
                        {entries.length === 0 && mounted ? (
                            <EmptyState
                                icon={<ListPlus size={24} />}
                                title="No journal entries yet"
                                description="Add the first debit and credit for this transaction."
                                action={
                                    permissions.editable ? (
                                        <Button
                                            variant="secondary"
                                            onClick={() =>
                                                setEntryModal({
                                                    mode: 'create',
                                                })
                                            }
                                        >
                                            Add entry
                                        </Button>
                                    ) : undefined
                                }
                            />
                        ) : (
                            <Skeleton
                                label="Loading journal entries"
                                lines={4}
                                loading={!mounted}
                            >
                                <EntriesTable
                                    entries={entries}
                                    editable={permissions.editable}
                                    onEdit={(entry) =>
                                        setEntryModal({
                                            mode: 'edit',
                                            entry,
                                        })
                                    }
                                    onDelete={(entry) => {
                                        setEntryTarget(entry);
                                        setActionError(null);
                                        setConfirming('delete-entry');
                                    }}
                                />
                            </Skeleton>
                        )}
                    </div>
                    {!permissions.editable ? (
                        <p className={styles.hint}>
                            Only draft transactions can take entries.
                        </p>
                    ) : null}
                </section>

                {entryModal ? (
                    <EntryFormModal
                        key={
                            entryModal.mode === 'edit'
                                ? `edit-entry-${entryModal.entry.id}`
                                : 'add-entry'
                        }
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setEntryModal(null);
                            }
                        }}
                        transactionId={transaction.id}
                        initial={
                            entryModal.mode === 'edit'
                                ? entryModal.entry
                                : null
                        }
                    />
                ) : null}

                {editing ? (
                    <TransactionFormModal
                        key={`edit-${transaction.ref}`}
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setEditing(false);
                            }
                        }}
                        mode="edit"
                        transactionId={transaction.id}
                        transactionRef={transaction.ref}
                        initial={{
                            description: transaction.description,
                            effective_date: transaction.effective_date,
                        }}
                    />
                ) : null}

                {confirming === 'submit' ? (
                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                            }
                        }}
                        title={`Submit ${transaction.ref}?`}
                        description="Submitting locks the entries and moves the transaction out of draft."
                        confirmLabel="Submit transaction"
                        processing={processing}
                        error={actionError}
                        onConfirm={() => runStatus('SUBMITTED')}
                    />
                ) : null}

                {confirming === 'cancel' ? (
                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                            }
                        }}
                        title={`Cancel ${transaction.ref}?`}
                        description="Cancelled transactions are kept for audit but can no longer be edited or posted."
                        confirmLabel="Cancel transaction"
                        danger
                        processing={processing}
                        error={actionError}
                        onConfirm={() => runStatus('CANCELLED')}
                    />
                ) : null}

                {confirming === 'post' ? (
                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                            }
                        }}
                        title={`Post ${transaction.ref}?`}
                        description="Posting writes the entries to the ledger balances. Posted transactions can only be reversed."
                        confirmLabel="Post transaction"
                        processing={processing}
                        error={actionError}
                        onConfirm={postTransaction}
                    />
                ) : null}

                {confirming === 'reverse' ? (
                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                            }
                        }}
                        title={`Reverse ${transaction.ref}?`}
                        description="A mirrored transaction is booked and posted, netting every balance back. The original is kept as reversed."
                        confirmLabel="Reverse transaction"
                        danger
                        processing={processing}
                        error={actionError}
                        onConfirm={reverseTransaction}
                    />
                ) : null}

                {confirming === 'delete-transaction' ? (                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                            }
                        }}
                        title={`Delete ${transaction.ref}?`}
                        description="This removes the transaction and its entries for good."
                        confirmLabel="Delete transaction"
                        danger
                        processing={processing}
                        error={actionError}
                        onConfirm={deleteTransaction}
                    />
                ) : null}

                {confirming === 'delete-entry' && entryTarget ? (
                    <ConfirmDialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirming(null);
                                setEntryTarget(null);
                            }
                        }}
                        title={`Remove entry ${entryTarget.id}?`}
                        description={`${entryTarget.account_code} ${entryTarget.account_name} will be removed from this transaction.`}
                        confirmLabel="Remove entry"
                        danger
                        processing={processing}
                        error={actionError}
                        onConfirm={deleteEntry}
                    />
                ) : null}
            </main>
        </>
    );
}

(
    TransactionShow as unknown as { layout: (page: ReactNode) => ReactNode }
).layout = (page) => (
    <AppLayout title="Transaction details" section="Transactions">
        {page}
    </AppLayout>
);
