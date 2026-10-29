import type { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, Scale } from 'lucide-react';
import { BarChart } from '@/components/arc/bar-chart/bar-chart';
import { DonutChart } from '@/components/arc/donut-chart/donut-chart';
import { LineChart } from '@/components/arc/line-chart/line-chart';
import { AppLayout } from '@/components/layout/AppLayout';
import { DateRangeFilter } from '@/components/overview/DateRangeFilter';
import { Panel } from '@/components/overview/Panel';
import { StatCard } from '@/components/overview/StatCard';
import { formatCompactNGN, formatNGN } from '@/lib/format';
import type { LedgerOverviewProps } from '@/types/overview';
import styles from './overview.module.css';

function dayLabel(iso: string): string {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('en-NG', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

function shortDay(iso: string): string {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('en-NG', {
        month: 'short',
        day: 'numeric',
    });
}

function longDate(iso: string): string {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('en-NG', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function numberLabel(value: number): string {
    return value.toLocaleString('en-NG');
}

export default function Overview({ filters, stats, activity, ledgers, top_accounts, recent }: LedgerOverviewProps) {
    const flow = activity.map((point) => ({
        key: point.date,
        label: dayLabel(point.date),
        axisLabel: shortDay(point.date),
        values: {
            debits: point.debits,
            credits: point.credits,
        },
    }));

    const volume = activity.map((point) => ({
        key: point.date,
        label: dayLabel(point.date),
        axisLabel: shortDay(point.date),
        value: point.transactions,
    }));

    const maxAccount = Math.max(...top_accounts.map((account) => account.value), 1);
    const period = `${longDate(filters.from)} – ${longDate(filters.to)}`;

    return (
        <>
            <Head title="General ledger overview" />
            <main className={styles.page}>
                <header className={styles.header}>
                    <div className={styles.heading}>
                        <h1 className={styles.title}>General ledger</h1>
                        <p className={styles.description}>
                            Posting flow, ledger activity and the accounts moving the books, with
                            every figure filtered to the selected period.
                        </p>
                    </div>
                    <DateRangeFilter filters={filters} url="/general-ledger" />
                </header>

                <div className={styles.stats}>
                    <StatCard
                        label="Posted transactions"
                        value={numberLabel(stats.posted_count)}
                        context={`${stats.pending_count} submitted · ${stats.draft_count} drafts`}
                        icon={<ArrowLeftRight size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Debit volume"
                        value={formatCompactNGN(stats.debits)}
                        context={`${formatNGN(stats.debits)} · ${stats.active_accounts} of ${stats.accounts} active`}
                        icon={<ArrowDownLeft size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Credit volume"
                        value={formatCompactNGN(stats.credits)}
                        context={`${formatNGN(stats.credits)} posted in this period`}
                        icon={<ArrowUpRight size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Net movement"
                        value={formatCompactNGN(stats.net)}
                        context="Debits less credits"
                        icon={<Scale size={16} strokeWidth={1.75} />}
                        tone={stats.net < 0 ? 'warning' : 'default'}
                    />
                </div>

                <div className={styles.charts}>
                    <Panel
                        className={styles.wide}
                        title="Posting flow"
                        description="Debits and credits posted per day."
                    >
                        <LineChart
                            label="Daily posting flow"
                            data={flow}
                            series={[
                                { key: 'debits', label: 'Debits' },
                                { key: 'credits', label: 'Credits' },
                            ]}
                            height={250}
                            formatValue={(value) => formatNGN(value)}
                            formatTick={(value) => formatCompactNGN(value)}
                            emptyLabel="No posted journals in this range"
                        />
                    </Panel>

                    <Panel title="Activity by ledger" description="Share of posted activity.">
                        <DonutChart
                            label="Posted activity by ledger"
                                                        data={ledgers}
                            formatValue={(value) => formatCompactNGN(value)}
                            totalLabel="Activity"
                            size={188}
                            emptyLabel="No activity"
                        />
                    </Panel>
                </div>

                <div className={styles.charts}>
                    <Panel title="Transaction count" description={`Daily postings · ${period}`}>
                        <BarChart
                            data={volume}
                            label="Posted transactions"
                            period={period}
                            unit="journals"
                            averageLabel="Daily average"
                            valueLabel="Transactions"
                            height={190}
                            formatValue={(value) => numberLabel(value)}
                        />
                    </Panel>

                    <Panel title="Accounts in motion" description="Highest posted activity in this period.">
                        {top_accounts.length === 0 ? (
                            <p className={styles.empty}>No account activity for this range.</p>
                        ) : (
                            <ul className={styles.accountList}>
                                {top_accounts.map((account) => (
                                    <li key={account.id} className={styles.accountRow}>
                                        <div className={styles.accountHead}>
                                            <span className={styles.accountMeta}>
                                                <span className={styles.accountCode}>{account.code}</span>
                                                <span className={styles.accountName}>{account.name}</span>
                                            </span>
                                            <span className={styles.accountValue}>{formatNGN(account.value)}</span>
                                        </div>
                                        <span className={styles.accountTrack} aria-hidden="true">
                                            <span
                                                className={styles.accountBar}
                                                style={{ width: `${Math.max(4, (account.value / maxAccount) * 100)}%` }}
                                            />
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <Panel
                    className={styles.full}
                    title="Recent postings"
                    description="The latest journals posted in this period."
                >
                    {recent.length === 0 ? (
                        <p className={styles.empty}>No posted transactions in this range.</p>
                    ) : (
                        <div className={styles.tableWrap}>
                            <table className={styles.table}>
                                <thead>
                                    <tr>
                                        <th scope="col">Ref</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Date</th>
                                        <th scope="col" className={styles.numeric}>
                                            Amount
                                        </th>
                                        <th scope="col" className={styles.numeric}>
                                            Entries
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recent.map((transaction) => (
                                        <tr key={transaction.id}>
                                            <td>
                                                <Link
                                                    className={styles.refLink}
                                                    href={`/general-ledger/transactions/${transaction.id}`}
                                                >
                                                    {transaction.ref}
                                                </Link>
                                            </td>
                                            <td className={styles.descriptionCell}>{transaction.description}</td>
                                            <td>{transaction.effective_date ? shortDay(transaction.effective_date) : '—'}</td>
                                            <td className={styles.numeric}>{formatNGN(transaction.amount)}</td>
                                            <td className={styles.numeric}>{transaction.entries}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Panel>
            </main>
        </>
    );
}

// Persistent layout: the sidebar and top bar stay mounted across visits.
(
    Overview as unknown as { layout: (page: ReactNode) => ReactNode }
).layout = (page) => (
    <AppLayout title="Overview" section="Workspace">
        {page}
    </AppLayout>
);
