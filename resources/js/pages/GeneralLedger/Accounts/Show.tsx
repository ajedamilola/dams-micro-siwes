import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Fragment } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { AppLayout } from '@/components/layout/AppLayout';
import { accountUrl, humanizeCategory } from '@/lib/accounts';
import type { ChartAccount } from '@/types/account';
import type { LedgerOption } from '@/types/ledger';
import { capitalize, formatNGN } from '@/lib/format';
import { AccountFormModal } from './components/AccountFormModal';
import { TYPE_TONE, type AccountRow } from './components/AccountsTable';
import styles from './components/show.module.css';
import formStyles from './components/accounts.module.css';

interface Ancestor {
    id: string;
    ledger_id: string;
    code: string;
    name: string;
}

interface AccountOption {
    id: string;
    code: string;
    name: string;
    parent_id: string | null;
    is_group: boolean;
}

interface ShowProps {
    account: ChartAccount;
    ancestors: Ancestor[];
    children: ChartAccount[];
    accounts: AccountOption[];
    ledgers: LedgerOption[];
}

export default function AccountShow({
    account,
    ancestors,
    children,
    accounts,
    ledgers,
}: ShowProps) {
    const [editing, setEditing] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const balance = Number(account.account_balance);
    const parent = ancestors[ancestors.length - 1] ?? null;

    const childRows = useMemo<AccountRow[]>(
        () =>
            children.map((child) => ({
                ...child,
                balance: Number(child.account_balance),
                actions: child.id,
            })),
        [children],
    );

    const columns = useMemo<DataColumn<AccountRow>[]>(
        () => [
            { key: 'code', label: 'Code', width: 92 },
            {
                key: 'name',
                label: 'Account name',
                sortable: false,
                render: (_value, row) => (
                    <Link
                        href={accountUrl(row.id, row.ledger_id)}
                        className={styles.nameLink}
                    >
                        {row.name}
                    </Link>
                ),
            },
            {
                key: 'type',
                label: 'Type',
                render: (value, row) => (
                    <Badge tone={TYPE_TONE[row.type]} size="sm">
                        {String(value)}
                    </Badge>
                ),
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge
                        tone={row.status === 'active' ? 'success' : 'neutral'}
                        size="sm"
                    >
                        {capitalize(String(value))}
                    </Badge>
                ),
            },
            {
                key: 'balance',
                label: 'Balance',
                width: 168,
                render: (value) => {
                    const amount = value as number;
                    return (
                        <span
                            className={amount < 0 ? styles.negative : undefined}
                        >
                            {formatNGN(amount)}
                        </span>
                    );
                },
            },
        ],
        [],
    );

    function deleteAccount(): void {
        router.delete(accountUrl(account.id, account.ledger_id), {
            onSuccess: () => setConfirmingDelete(false),
            onError: (errors) =>
                setDeleteError(
                    Object.values(errors)[0] ??
                        'This account could not be deleted.',
                ),
        });
    }

    const facts: { label: string; value: ReactNode }[] = [
        {
            label: 'Code',
            value: <span className={styles.code}>{account.code}</span>,
        },
        { label: 'Type', value: account.type },
        {
            label: 'Category',
            value: humanizeCategory(account.category ?? 'Uncategorized'),
        },
        {
            label: 'Normal balance',
            value: capitalize(account.balance_type),
        },
        {
            label: 'Status',
            value: (
                <Badge
                    tone={account.status === 'active' ? 'success' : 'neutral'}
                    size="sm"
                >
                    {capitalize(account.status)}
                </Badge>
            ),
        },
        {
            label: 'Ledger',
            value: `${account.ledger_code ?? ''} ${account.ledger_name ?? ''}`.trim(),
        },
        {
            label: 'Company',
            value: account.company_name ?? '—',
        },
        {
            label: 'Parent',
            value: parent ? (
                <Link
                    href={accountUrl(parent.id, parent.ledger_id)}
                    className={styles.nameLink}
                >
                    {parent.code} {parent.name}
                </Link>
            ) : (
                'Top level'
            ),
        },
        {
            label: 'Hierarchy level',
            value: `Level ${account.level}`,
        },
        {
            label: 'Sub-accounts',
            value: String(children.length),
        },
    ];

    return (
        <>
            <Head title={`${account.code} ${account.name}`} />
            <main className={styles.page}>
                <nav className={styles.crumbs} aria-label="Breadcrumb">
                    <ol>
                        <li>
                            <Link
                                href="/general-ledger/accounts"
                                className={styles.crumbLink}
                            >
                                Chart of accounts
                            </Link>
                        </li>
                        {ancestors.map((ancestor) => (
                            <Fragment key={ancestor.id}>
                                <li aria-hidden="true">/</li>
                                <li>
                                    <Link
                                        href={accountUrl(
                                            ancestor.id,
                                            ancestor.ledger_id,
                                        )}
                                        className={styles.crumbLink}
                                    >
                                        {ancestor.name}
                                    </Link>
                                </li>
                            </Fragment>
                        ))}
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{account.name}</li>
                    </ol>
                </nav>

                <div className={styles.header}>
                    <div>
                        <h1 className={styles.title}>{account.name}</h1>
                        <div className={styles.badges}>
                            <Badge tone={TYPE_TONE[account.type]} size="sm">
                                {account.type}
                            </Badge>
                            <Badge
                                tone={
                                    account.status === 'active'
                                        ? 'success'
                                        : 'neutral'
                                }
                                size="sm"
                            >
                                {capitalize(account.status)}
                            </Badge>
                        </div>
                    </div>
                    <div className={styles.actions}>
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setDeleteError(null);
                                setConfirmingDelete(true);
                            }}
                        >
                            Delete
                        </Button>
                        <Button onClick={() => setEditing(true)}>Edit</Button>
                    </div>
                </div>

                <div className={styles.topGrid}>
                    <section
                        className={styles.hero}
                        aria-labelledby="balance-heading"
                    >
                        <h2 id="balance-heading" className={styles.heroLabel}>
                            Current balance
                        </h2>
                        <p
                            className={`${styles.heroValue} ${balance < 0 ? styles.negative : ''}`}
                        >
                            {formatNGN(balance)}
                        </p>
                        <p className={styles.heroNote}>
                            {account.is_group
                                ? `Rolls up from ${account.children_count} ${account.children_count === 1 ? 'sub-account' : 'sub-accounts'}.`
                                : 'Postable account. Entries post directly here.'}
                        </p>
                    </section>

                    <section aria-labelledby="details-heading">
                        <h2
                            id="details-heading"
                            className={styles.sectionTitle}
                        >
                            Details
                        </h2>
                        <dl className={styles.facts}>
                            {facts.map((fact) => (
                                <div key={fact.label} className={styles.fact}>
                                    <dt>{fact.label}</dt>
                                    <dd>{fact.value}</dd>
                                </div>
                            ))}
                        </dl>
                        {account.description ? (
                            <p className={styles.description}>
                                {account.description}
                            </p>
                        ) : null}
                    </section>
                </div>

                <section aria-labelledby="children-heading">
                    <h2 id="children-heading" className={styles.sectionTitle}>
                        Sub-accounts
                    </h2>
                    {childRows.length > 0 ? (
                        <SortableDataTable
                            rows={childRows}
                            columns={columns}
                            rowKey="id"
                            caption={`Sub-accounts of ${account.name}`}
                            emptyMessage="No sub-accounts to show"
                            defaultSort={{ key: 'code', direction: 'asc' }}
                            itemName={{
                                one: 'account',
                                other: 'accounts',
                            }}
                        />
                    ) : (
                        <p className={styles.heroNote}>
                            No sub-accounts. This is a postable account.
                        </p>
                    )}
                </section>

                {editing ? (
                    <AccountFormModal
                        key={`edit-${account.id}`}
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setEditing(false);
                            }
                        }}
                        mode="edit"
                        initial={account}
                        rows={accounts.map((row) => ({
                            id: row.id,
                            code: row.code,
                            name: row.name,
                            parent_id: row.parent_id,
                            is_group: row.is_group,
                            ledger_id: account.ledger_id,
                        }))}
                        ledgers={ledgers}
                        defaultLedgerId={account.ledger_id}
                    />
                ) : null}

                {confirmingDelete ? (
                    <Dialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirmingDelete(false);
                            }
                        }}
                    >
                        <DialogContent
                            title={`Delete ${account.code} ${account.name}?`}
                            description="This removes the account for good. Accounts with sub-accounts or journal entries cannot be deleted."
                        >
                            {deleteError ? (
                                <p
                                    className={formStyles.formError}
                                    role="alert"
                                >
                                    {deleteError}
                                </p>
                            ) : null}
                            <div className={formStyles.formActions}>
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
(AccountShow as unknown as { layout: (page: ReactNode) => ReactNode }).layout =
    (page) => (
        <AppLayout title="Account details" section="Chart of accounts">
            {page}
        </AppLayout>
    );
