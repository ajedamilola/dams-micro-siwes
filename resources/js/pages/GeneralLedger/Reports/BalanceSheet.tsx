import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { Input } from '@/components/arc/input/input';
import { AppLayout } from '@/components/layout/AppLayout';
import { formatNGN } from '@/lib/format';
import { formatDate } from '@/lib/transactions';
import type {
    BalanceSheetGroup,
    BalanceSheetLine,
    BalanceSheetStatement,
    ReportFilters,
} from '@/types/ledger';
import { Eye, EyeOff } from 'lucide-react';
import styles from './components/reports.module.css';

interface BalanceSheetProps {
    statement: BalanceSheetStatement;
    filters: ReportFilters;
    business_date: string;
}

function Amount({ value }: { value: string }) {
    return (
        <span
            className={`${styles.bsAmount} ${Number(value) < 0 ? styles.negative : ''}`}
        >
            {formatNGN(Number(value))}
        </span>
    );
}

function Groups({
    groups,
    showDetails,
}: {
    groups: BalanceSheetGroup[];
    showDetails: boolean;
}) {
    return (
        <>
            {groups.map((group) => (
                <div key={group.label}>
                    {showDetails ? (
                        <>
                            <h4 className={styles.subhead}>{group.label}</h4>
                            {group.lines.map((line) => (
                                <div
                                    className={styles.bsRow}
                                    key={line.code ?? line.name}
                                >
                                    <span className={styles.bsName}>
                                        {line.name}
                                    </span>
                                    <Amount value={line.amount} />
                                </div>
                            ))}
                        </>
                    ) : null}
                    <div className={styles.bsTotal}>
                        <span>Total {group.label.toLowerCase()}</span>
                        <Amount value={group.total} />
                    </div>
                </div>
            ))}
        </>
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

export default function BalanceSheet({
    statement,
    filters,
    business_date,
}: BalanceSheetProps) {
    const [showDetails, setShowDetails] = useState(false);
    const balanced =
        Number(statement.assets_total) ===
        Number(statement.liabilities_total) + Number(statement.equity_total);

    function reload(asAt: string): void {
        router.get(
            '/general-ledger/balance-sheet',
            { as_at: asAt },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Balance sheet" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <h1 className={styles.title}>Balance sheet</h1>
                    <p className={styles.description}>
                        {statement.company.name} · As at{' '}
                        {formatDate(statement.as_at)}. Headings follow the
                        report settings.
                    </p>
                </div>

                <AsAtFilter
                    value={filters.as_at}
                    max={business_date}
                    onApply={reload}
                />

                <div className={styles.toolbar}>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        onClick={() => setShowDetails((value) => !value)}
                        aria-pressed={showDetails}
                    >
                        {showDetails ? (
                            <EyeOff size={15} aria-hidden="true" />
                        ) : (
                            <Eye size={15} aria-hidden="true" />
                        )}
                        {showDetails
                            ? 'Hide detail accounts'
                            : 'Show detail accounts'}
                    </Button>
                </div>

                <div className={styles.sheetGrid}>
                    <section
                        className={styles.card}
                        aria-labelledby="assets-heading"
                    >
                        <h2 id="assets-heading" className={styles.cardTitle}>
                            Assets
                        </h2>
                        <Groups
                            groups={statement.assets_current}
                            showDetails={showDetails}
                        />
                        <Groups
                            groups={statement.assets_noncurrent}
                            showDetails={showDetails}
                        />
                        <div className={styles.bsTotal}>
                            <span>Total assets</span>
                            <Amount value={statement.assets_total} />
                        </div>
                    </section>

                    <div className={styles.stack}>
                        <section
                            className={styles.card}
                            aria-labelledby="liabilities-heading"
                        >
                            <h2
                                id="liabilities-heading"
                                className={styles.cardTitle}
                            >
                                Liabilities
                            </h2>
                            <Groups
                                groups={statement.liabilities_current}
                                showDetails={showDetails}
                            />
                            <Groups
                                groups={statement.liabilities_noncurrent}
                                showDetails={showDetails}
                            />
                            <div className={styles.bsTotal}>
                                <span>Total liabilities</span>
                                <Amount value={statement.liabilities_total} />
                            </div>
                        </section>

                        <section
                            className={styles.card}
                            aria-labelledby="equity-heading"
                        >
                            <h2
                                id="equity-heading"
                                className={styles.cardTitle}
                            >
                                Equity
                            </h2>
                            <Groups
                                groups={statement.equity}
                                showDetails={showDetails}
                            />
                            <div className={styles.bsRow}>
                                <span className={styles.bsName}>
                                    {statement.surplus.name}
                                </span>
                                <Amount value={statement.surplus.amount} />
                            </div>
                            <div className={styles.bsTotal}>
                                <span>Total equity</span>
                                <Amount value={statement.equity_total} />
                            </div>
                            <div className={styles.bsTotal}>
                                <span>Total liabilities and equity</span>
                                <span className={styles.bsAmount}>
                                    {formatNGN(
                                        Number(statement.liabilities_total) +
                                            Number(statement.equity_total),
                                    )}
                                </span>
                            </div>
                        </section>
                    </div>
                </div>

                <p className={styles.hint}>
                    <Badge tone={balanced ? 'success' : 'danger'} size="sm">
                        {balanced ? 'Balanced' : 'Out of balance'}
                    </Badge>
                </p>
            </main>
        </>
    );
}

// Persistent layout: the sidebar and top bar stay mounted across visits.
(BalanceSheet as unknown as { layout: (page: ReactNode) => ReactNode }).layout =
    (page) => (
        <AppLayout title="Balance sheet" section="General ledger">
            {page}
        </AppLayout>
    );
