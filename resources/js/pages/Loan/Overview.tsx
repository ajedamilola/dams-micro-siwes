import type { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeftRight,
    Banknote,
    HandCoins,
    Percent,
    Wallet,
} from 'lucide-react';
import { Badge } from '@/components/arc/badge/badge';
import type { BadgeTone } from '@/components/arc/badge/badge';
import { DonutChart } from '@/components/arc/donut-chart/donut-chart';
import { LineChart } from '@/components/arc/line-chart/line-chart';
import { AppLayout } from '@/components/layout/AppLayout';
import { DateRangeFilter } from '@/components/overview/DateRangeFilter';
import { Panel } from '@/components/overview/Panel';
import { StatCard } from '@/components/overview/StatCard';
import { formatCompactNGN, formatNGN } from '@/lib/format';
import type { LoanOverviewProps, LoanStatusSlice } from '@/types/overview';
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

function statusTone(status: string): BadgeTone {
    switch (status) {
        case 'active':
            return 'success';
        case 'approved':
            return 'info';
        case 'pending_approval':
            return 'warning';
        case 'rejected':
        case 'written_off':
            return 'danger';
        default:
            return 'neutral';
    }
}

function statusLabel(status: string): string {
    return status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function StatusStrip({ status }: { status: LoanStatusSlice[] }) {
    if (status.length === 0) {
        return null;
    }

    return (
        <div className={styles.statusStrip}>
            <span className={styles.statusLabel}>Loan book</span>
            {status.map((entry) => (
                <Badge key={entry.key} tone={statusTone(entry.key)}>
                    {`${statusLabel(entry.key)} ${entry.count}`}
                </Badge>
            ))}
        </div>
    );
}

export default function Overview({ filters, stats, activity, portfolio, status, recent }: LoanOverviewProps) {
    const flow = activity.map((point) => ({
        key: point.date,
        label: dayLabel(point.date),
        axisLabel: shortDay(point.date),
        values: {
            disbursed: point.disbursed,
            collected: point.collected,
        },
    }));

    const mix = [
        { key: 'principal', label: 'Principal', value: stats.principal_collected },
        { key: 'interest', label: 'Interest', value: stats.interest },
        { key: 'fees', label: 'Fees', value: stats.fees },
        { key: 'penalties', label: 'Penalties', value: stats.penalties },
    ].filter((slice) => slice.value > 0);

    return (
        <>
            <Head title="Loan overview" />
            <main className={styles.page}>
                <header className={styles.header}>
                    <div className={styles.heading}>
                        <h1 className={styles.title}>Loan portfolio</h1>
                        <p className={styles.description}>
                            Disbursements, collections and the outstanding book by product, with
                            every figure filtered to the selected period.
                        </p>
                    </div>
                    <DateRangeFilter filters={filters} url="/loans" />
                </header>

                <div className={styles.stats}>
                    <StatCard
                        label="Principal disbursed"
                        value={formatCompactNGN(stats.disbursed)}
                        context={`${stats.disbursed_count} loans disbursed`}
                        icon={<HandCoins size={16} strokeWidth={1.75} />}
                        tone="positive"
                    />
                    <StatCard
                        label="Repayments collected"
                        value={formatCompactNGN(stats.collected)}
                        context={`${stats.collected_count} ${stats.collected_count === 1 ? 'repayment' : 'repayments'} received`}
                        icon={<Banknote size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Interest collected"
                        value={formatCompactNGN(stats.interest)}
                        context={`Fees ${formatCompactNGN(stats.fees)} · penalties ${formatCompactNGN(stats.penalties)}`}
                        icon={<Percent size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Outstanding portfolio"
                        value={formatCompactNGN(stats.outstanding)}
                        context={`${stats.active_loans} active loans · ${stats.pending_approvals} awaiting approval`}
                        icon={<Wallet size={16} strokeWidth={1.75} />}
                    />
                    <StatCard
                        label="Overdue loans"
                        value={stats.overdue_loans.toLocaleString('en-NG')}
                        context="Active loans with past-due instalments"
                        icon={<AlertTriangle size={16} strokeWidth={1.75} />}
                        tone={stats.overdue_loans > 0 ? 'warning' : 'default'}
                    />
                </div>

                <StatusStrip status={status} />

                <div className={styles.charts}>
                    <Panel
                        className={styles.wide}
                        title="Disbursements and collections"
                        description="Cash out and cash in per day."
                    >
                        <LineChart
                            label="Daily loan cash flow"
                            data={flow}
                            series={[
                                { key: 'disbursed', label: 'Disbursed' },
                                { key: 'collected', label: 'Collected' },
                            ]}
                            height={250}
                            formatValue={(value) => formatNGN(value)}
                            formatTick={(value) => formatCompactNGN(value)}
                            emptyLabel="No loan cash flow in this range"
                        />
                    </Panel>

                    <Panel title="Outstanding by product" description="Active loan balances.">
                        <DonutChart
                            label="Outstanding portfolio by product"
                            data={portfolio}
                            formatValue={(value) => formatCompactNGN(value)}
                            totalLabel="Outstanding"
                            size={188}
                            emptyLabel="No active loans"
                        />
                    </Panel>
                </div>

                <div className={styles.charts}>
                    <Panel
                        title="Recent loans"
                        description={`Latest bookings · ${longDate(filters.from)} – ${longDate(filters.to)}`}
                        action={
                            <Link className={styles.panelLink} href="/loans/loans">
                                View all
                            </Link>
                        }
                    >
                        {recent.length === 0 ? (
                            <p className={styles.empty}>No loans booked yet.</p>
                        ) : (
                            <div className={styles.tableWrap}>
                                <table className={styles.table}>
                                    <thead>
                                        <tr>
                                            <th scope="col">Loan</th>
                                            <th scope="col">Customer</th>
                                            <th scope="col" className={styles.numeric}>
                                                Principal
                                            </th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recent.map((loan) => (
                                            <tr key={loan.id}>
                                                <td>
                                                    <Link className={styles.refLink} href={`/loans/loans/${loan.id}`}>
                                                        {loan.loan_no}
                                                    </Link>
                                                </td>
                                                <td className={styles.nameCell}>{loan.customer}</td>
                                                <td className={styles.numeric}>{formatNGN(loan.principal)}</td>
                                                <td>
                                                    <Badge size="sm" tone={statusTone(loan.status)}>
                                                        {statusLabel(loan.status)}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Panel>

                    <Panel title="Repayment mix" description="How collections split in this period.">
                        {mix.length === 0 ? (
                            <p className={styles.empty}>No repayments in this range.</p>
                        ) : (
                            <DonutChart
                                label="Repayment mix"
                                data={mix}
                                formatValue={(value) => formatCompactNGN(value)}
                                totalLabel="Collected"
                                size={188}
                                emptyLabel="No collections"
                            />
                        )}
                        <div className={styles.mixNote}>
                            <ArrowLeftRight size={15} strokeWidth={1.75} aria-hidden="true" />
                            <span>Principal, interest, fees and penalties allocated oldest instalment first.</span>
                        </div>
                    </Panel>
                </div>
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
