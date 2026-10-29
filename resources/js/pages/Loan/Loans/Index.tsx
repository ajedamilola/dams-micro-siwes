import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { AppLayout } from '@/components/layout/AppLayout';
import type { ProductOption } from '@/types/loan';
import { BookLoanModal } from './components/BookLoanModal';
import { LoanToolbar, type LoanStatusFilter } from './components/LoanToolbar';
import { LoansTable, type LoanBrief, type LoanBriefRow } from './components/LoansTable';
import styles from './components/loans.module.css';

interface LoansIndexProps {
    loans: LoanBrief[];
    products: ProductOption[];
    filters: { status: string; q: string };
}

export default function LoansIndex({ loans, products, filters }: LoansIndexProps) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [statusFilter, setStatusFilter] = useState<LoanStatusFilter>(
        (filters.status as LoanStatusFilter) || 'all',
    );
    const [booking, setBooking] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const rows = useMemo<LoanBriefRow[]>(() => loans.map((loan) => ({ ...loan })), [loans]);

    const filteredRows = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return rows.filter((row) => {
            if (statusFilter !== 'all' && row.status !== statusFilter) {
                return false;
            }
            if (needle === '') {
                return true;
            }
            return (
                row.loan_no.toLowerCase().includes(needle) ||
                (row.product_name ?? '').toLowerCase().includes(needle) ||
                (row.customer_name ?? '').toLowerCase().includes(needle)
            );
        });
    }, [rows, query, statusFilter]);

    const filtersActive = query.trim() !== '' || statusFilter !== 'all';

    const activeLoans = rows.filter((row) => row.status === 'active');
    const activePrincipal = activeLoans.reduce((sum, row) => sum + Number(row.principal), 0);
    const pendingCount = rows.filter((row) => row.status === 'pending_approval').length;

    function clearFilters(): void {
        setQuery('');
        setStatusFilter('all');
    }

    return (
        <>
            <Head title="Loans" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div>
                        <h1 className={styles.title}>Loans</h1>
                        <p className={styles.subtitle}>
                            Apply, approve, disburse and collect. Every money movement posts a
                            balanced journal for the GL team to review and post.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button onClick={() => setBooking(true)}>Book a loan</Button>
                    </div>
                </div>

                <div className={styles.stats} role="group" aria-label="Loan book stats">
                    <section className={styles.statCard} aria-label="Active loans">
                        <h2 className={styles.statLabel}>Active loans</h2>
                        <p className={styles.statValue}>{activeLoans.length}</p>
                        <p className={styles.statNote}>
                            {activePrincipal.toLocaleString('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 })}{' '}
                            principal outstanding.
                        </p>
                    </section>
                    <section className={styles.statCard} aria-label="Pending approval">
                        <h2 className={styles.statLabel}>Pending approval</h2>
                        <p className={styles.statValue}>{pendingCount}</p>
                        <p className={styles.statNote}>Applications waiting for a decision.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Total book">
                        <h2 className={styles.statLabel}>Total book</h2>
                        <p className={styles.statValue}>{rows.length}</p>
                        <p className={styles.statNote}>Loans across every status.</p>
                    </section>
                </div>

                <LoanToolbar
                    query={query}
                    onQueryChange={setQuery}
                    statusFilter={statusFilter}
                    onStatusChange={setStatusFilter}
                />

                <LoansTable
                    rows={filteredRows}
                    totalFiltered={filteredRows.length}
                    totalAll={rows.length}
                    onView={(row) => router.visit(`/loans/loans/${row.id}`)}
                    filtersActive={filtersActive}
                    onClearFilters={clearFilters}
                    loading={!mounted}
                />

                {booking ? (
                    <BookLoanModal open onOpenChange={(open) => !open && setBooking(false)} products={products} />
                ) : null}
            </main>
        </>
    );
}

(LoansIndex as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Loans" section="Loan">
        {page}
    </AppLayout>
);
