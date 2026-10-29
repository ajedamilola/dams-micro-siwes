import { useMemo, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { Input } from '@/components/arc/input/input';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { AppLayout } from '@/components/layout/AppLayout';
import { accountUrl } from '@/lib/accounts';
import { formatNGN } from '@/lib/format';
import { formatDate } from '@/lib/transactions';
import type { ReportFilters, TrialBalanceStatement } from '@/types/ledger';
import styles from './components/reports.module.css';

interface TrialBalanceProps {
    statement: TrialBalanceStatement;
    filters: ReportFilters;
    business_date: string;
}

type TrialRow = TrialBalanceStatement['rows'][number];

function amountCell(value: string): ReactNode {
    return Number(value) > 0 ? (
        formatNGN(Number(value))
    ) : (
        <span className={styles.dash} aria-hidden="true">
            –
        </span>
    );
}

function AsAtFilter({
    value,
    max,
    onApply,
}: {
    value: string;
    max: string;
    onApply: (asAt: string) => void;
}) {
    const [date, setDate] = useState(value);

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        if (date) {
            onApply(date);
        }
    }

    return (
        <form className={styles.filterRow} onSubmit={handleSubmit} noValidate>
            <Input
                label="As at"
                type="date"
                value={date}
                max={max}
                onChange={(event) => setDate(event.target.value)}
            />
            <Button type="submit" variant="secondary" size="sm">
                Apply
            </Button>
        </form>
    );
}

export default function TrialBalance({
    statement,
    filters,
    business_date,
}: TrialBalanceProps) {
    const columns = useMemo<DataColumn<TrialRow>[]>(
        () => [
            { key: 'code', label: 'Code', width: 92 },
            {
                key: 'name',
                label: 'Account',
                sortable: false,
                render: (_value, row) => (
                    <Link
                        href={accountUrl(
                            row.account_id,
                            row.account_ledger_id,
                        )}
                        className={styles.nameLink}
                    >
                        {row.name}
                    </Link>
                ),
            },
            {
                key: 'debit',
                label: 'Debit',
                width: 168,
                render: (value) => amountCell(String(value)),
            },
            {
                key: 'credit',
                label: 'Credit',
                width: 168,
                render: (value) => amountCell(String(value)),
            },
        ],
        [],
    );

    const balanced =
        Number(statement.debit_total) === Number(statement.credit_total);

    function reload(asAt: string): void {
        router.get(
            '/general-ledger/trial-balance',
            { as_at: asAt },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Trial balance" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <h1 className={styles.title}>Trial balance</h1>
                    <p className={styles.description}>
                        {statement.company.name} · As at{' '}
                        {formatDate(statement.as_at)}. From opening balances and
                        posted journals.
                    </p>
                </div>

                <AsAtFilter
                    value={filters.as_at}
                    max={business_date}
                    onApply={reload}
                />

                <section aria-labelledby="balances-heading">
                    <div className={styles.sectionHead}>
                        <div>
                            <h2
                                id="balances-heading"
                                className={styles.sectionTitle}
                            >
                                Balances
                            </h2>
                            <p className={styles.count}>
                                {statement.rows.length} accounts
                            </p>
                        </div>
                    </div>

                    <div className={styles.tableWrap}>
                        <SortableDataTable
                            rows={statement.rows}
                            columns={columns}
                            rowKey="code"
                            caption={`Trial balance of ${statement.company.name}`}
                            emptyMessage="No balances to show"
                            defaultSort={{ key: 'code', direction: 'asc' }}
                            itemName={{
                                one: 'account',
                                other: 'accounts',
                            }}
                        />
                    </div>
                </section>

                <section aria-label="Trial balance totals">
                    <div className={styles.strip}>
                        <div className={styles.stripCell}>
                            <p className={styles.stripLabel}>Total debits</p>
                            <p className={styles.stripValue}>
                                {formatNGN(Number(statement.debit_total))}
                            </p>
                        </div>
                        <div className={styles.stripCell}>
                            <p className={styles.stripLabel}>Total credits</p>
                            <p className={styles.stripValue}>
                                {formatNGN(Number(statement.credit_total))}
                            </p>
                        </div>
                        <div className={styles.stripCell}>
                            <p className={styles.stripLabel}>Difference</p>
                            <p className={styles.stripValue}>
                                {formatNGN(
                                    Number(statement.debit_total) -
                                        Number(statement.credit_total),
                                )}
                            </p>
                        </div>
                    </div>
                    <p className={styles.hint}>
                        <Badge
                            tone={balanced ? 'success' : 'danger'}
                            size="sm"
                        >
                            {balanced ? 'Balanced' : 'Out of balance'}
                        </Badge>
                    </p>
                </section>
            </main>
        </>
    );
}

// Persistent layout: the sidebar and top bar stay mounted across visits.
(TrialBalance as unknown as { layout: (page: ReactNode) => ReactNode }).layout =
    (page) => (
        <AppLayout title="Trial balance" section="General ledger">
            {page}
        </AppLayout>
    );
