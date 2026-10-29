import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { AppLayout } from '@/components/layout/AppLayout';
import type { CompanyOption } from '@/types/ledger';
import type { LoanProduct } from '@/types/loan';
import { ProductToolbar, type ProductFrequencyFilter, type ProductMethodFilter, type ProductStatusFilter } from './components/ProductToolbar';
import { ProductFormModal } from './components/ProductFormModal';
import { ProductsTable, type ProductRow } from './components/ProductsTable';
import styles from './components/products.module.css';

interface ProductsIndexProps {
    products: LoanProduct[];
    companies: CompanyOption[];
}

export default function ProductsIndex({ products, companies }: ProductsIndexProps) {
    const [query, setQuery] = useState('');
    const [methodFilter, setMethodFilter] = useState<ProductMethodFilter>('all');
    const [frequencyFilter, setFrequencyFilter] = useState<ProductFrequencyFilter>('all');
    const [statusFilter, setStatusFilter] = useState<ProductStatusFilter>('all');
    const [modal, setModal] = useState<{ mode: 'create' } | { mode: 'edit'; id: string } | null>(null);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const rows = useMemo<ProductRow[]>(() => products.map((product) => ({ ...product })), [products]);

    const filteredRows = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return rows.filter((row) => {
            if (methodFilter !== 'all' && row.interest_method !== methodFilter) {
                return false;
            }
            if (frequencyFilter !== 'all' && row.repayment_frequency !== frequencyFilter) {
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
                (row.company_name ?? '').toLowerCase().includes(needle)
            );
        });
    }, [rows, query, methodFilter, frequencyFilter, statusFilter]);

    const filtersActive =
        query.trim() !== '' || methodFilter !== 'all' || frequencyFilter !== 'all' || statusFilter !== 'all';

    const activeCount = rows.filter((row) => row.status === 'active').length;
    const loansBooked = rows.reduce((sum, row) => sum + row.loans_count, 0);

    function clearFilters(): void {
        setQuery('');
        setMethodFilter('all');
        setFrequencyFilter('all');
        setStatusFilter('all');
    }

    return (
        <>
            <Head title="Loan products" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>Loan products</h1>
                        <p className={styles.description}>
                            Terms are defined once per product. Every loan inherits them, and every
                            posting follows the six-account interfacing map.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button onClick={() => setModal({ mode: 'create' })}>New product</Button>
                    </div>
                </div>

                <div className={styles.stats} role="group" aria-label="Product stats">
                    <section className={styles.statCard} aria-label="Total products">
                        <h2 className={styles.statLabel}>Products</h2>
                        <p className={styles.statValue}>{rows.length}</p>
                        <p className={styles.statNote}>{activeCount} active, ready to book loans.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Loans booked">
                        <h2 className={styles.statLabel}>Loans booked</h2>
                        <p className={styles.statValue}>{loansBooked}</p>
                        <p className={styles.statNote}>Across all products and statuses.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Interfacing">
                        <h2 className={styles.statLabel}>Interfacing</h2>
                        <p className={styles.statValue}>6 maps</p>
                        <p className={styles.statNote}>Principal, interest, fee and penalty legs per product.</p>
                    </section>
                </div>

                <ProductToolbar
                    query={query}
                    onQueryChange={setQuery}
                    methodFilter={methodFilter}
                    onMethodChange={setMethodFilter}
                    frequencyFilter={frequencyFilter}
                    onFrequencyChange={setFrequencyFilter}
                    statusFilter={statusFilter}
                    onStatusChange={setStatusFilter}
                />

                <ProductsTable
                    rows={filteredRows}
                    totalFiltered={filteredRows.length}
                    totalAll={rows.length}
                    onView={(row) => router.visit(`/loans/products/${row.id}`)}
                    onEdit={(row) => setModal({ mode: 'edit', id: row.id })}
                    filtersActive={filtersActive}
                    onClearFilters={clearFilters}
                    loading={!mounted}
                />

                {modal ? (
                    <ProductFormModal
                        key={modal.mode === 'edit' ? `edit-${modal.id}` : 'create'}
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setModal(null);
                            }
                        }}
                        mode={modal.mode}
                        initial={modal.mode === 'edit' ? (rows.find((row) => row.id === modal.id) ?? null) : null}
                        companies={companies}
                    />
                ) : null}
            </main>
        </>
    );
}

(ProductsIndex as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Loan products" section="Loan">
        {page}
    </AppLayout>
);
