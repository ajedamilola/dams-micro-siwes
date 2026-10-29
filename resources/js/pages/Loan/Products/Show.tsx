import { useState } from 'react';
import type { ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { AppLayout } from '@/components/layout/AppLayout';
import { formatNGN } from '@/lib/format';
import { humanizeToken } from '@/lib/loan-customers';
import type { LoanProduct, RecentLoan } from '@/types/loan';
import { OPERATION_META } from '@/types/loan';
import { ProductFormModal } from './components/ProductFormModal';
import styles from './components/products.module.css';

type LoanRow = RecentLoan & Record<string, unknown>;

const STATUS_TONE: Record<string, 'success' | 'info' | 'neutral' | 'danger'> = {
    active: 'success',
    closed: 'neutral',
    pending_approval: 'info',
    approved: 'info',
    rejected: 'danger',
    draft: 'neutral',
    written_off: 'danger',
};

export default function ProductShow({ product }: { product: LoanProduct }) {
    const [editing, setEditing] = useState(false);
    const recentLoans: LoanRow[] = (product.recent_loans ?? []).map((loan) => ({ ...loan }));

    const columns: DataColumn<LoanRow>[] = [
        {
            key: 'loan_no',
            label: 'Loan',
            width: 140,
            render: (value, row) => (
                <Link href={`/loans/loans/${row.id}`} className={styles.crumbLink}>
                    {String(value)}
                </Link>
            ),
        },
        { key: 'customer_name', label: 'Customer', sortable: false },
        {
            key: 'principal',
            label: 'Principal',
            width: 150,
            render: (value) => <span className={styles.mono}>{formatNGN(Number(value))}</span>,
        },
        {
            key: 'status',
            label: 'Status',
            render: (value, row) => (
                <Badge tone={STATUS_TONE[row.status] ?? 'neutral'} size="sm">
                    {String(value).replaceAll('_', ' ')}
                </Badge>
            ),
        },
    ];

    const termsFacts: { label: string; value: ReactNode }[] = [
        { label: 'Company', value: product.company_name ?? 'None' },
        {
            label: 'Interest',
            value: `${humanizeToken(product.interest_method)} · ${product.annual_interest_rate}% p.a.`,
        },
        { label: 'Frequency', value: humanizeToken(product.repayment_frequency) },
        {
            label: 'Principal range',
            value: `${formatNGN(Number(product.min_principal))} – ${formatNGN(Number(product.max_principal))}`,
        },
        { label: 'Tenor range', value: `${product.min_tenor} – ${product.max_tenor} instalments` },
        {
            label: 'Processing fee',
            value: `${product.processing_fee_rate}%`,
        },
        {
            label: 'Penalty',
            value: `${product.penalty_rate_daily}% per day after ${product.penalty_grace_days} grace days`,
        },
    ];

    return (
        <>
            <Head title={`${product.code} ${product.name}`} />
            <main className={styles.page}>
                <nav className={styles.crumbs} aria-label="Breadcrumb">
                    <ol>
                        <li>
                            <Link href="/loans/products" className={styles.crumbLink}>
                                Loan products
                            </Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{product.code}</li>
                    </ol>
                </nav>

                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>{product.name}</h1>
                        <p className={styles.description}>{product.description || 'No description.'}</p>
                        <div className={styles.badges}>
                            <Badge tone="neutral" size="sm">
                                {product.code}
                            </Badge>
                            <Badge tone={product.interest_method === 'flat' ? 'info' : 'warning'} size="sm">
                                {humanizeToken(product.interest_method)}
                            </Badge>
                            <Badge tone="info" size="sm">
                                {humanizeToken(product.repayment_frequency)}
                            </Badge>
                            <Badge tone={product.status === 'active' ? 'success' : 'neutral'} size="sm">
                                {product.status}
                            </Badge>
                        </div>
                    </div>
                    <div className={styles.actions}>
                        <Button variant="secondary" onClick={() => setEditing(true)}>
                            Edit terms
                        </Button>
                        <Button onClick={() => router.visit(`/loans/loans?product=${product.id}`)}>
                            Book a loan
                        </Button>
                    </div>
                </div>

                <div className={styles.stats} role="group" aria-label="Product stats">
                    <section className={styles.statCard} aria-label="Annual rate">
                        <h2 className={styles.statLabel}>Annual rate</h2>
                        <p className={styles.statValue}>{product.annual_interest_rate}%</p>
                        <p className={styles.statNote}>{humanizeToken(product.interest_method)} interest.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Principal range">
                        <h2 className={styles.statLabel}>Principal range</h2>
                        <p className={styles.statValue}>{formatNGN(Number(product.max_principal))}</p>
                        <p className={styles.statNote}>From {formatNGN(Number(product.min_principal))}.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Loans booked">
                        <h2 className={styles.statLabel}>Loans booked</h2>
                        <p className={styles.statValue}>{product.loans_count}</p>
                        <p className={styles.statNote}>
                            Tenor {product.min_tenor}–{product.max_tenor} {product.repayment_frequency} instalments.
                        </p>
                    </section>
                </div>

                <section aria-labelledby="map-heading">
                    <h2 id="map-heading" className={styles.sectionTitle}>
                        Interfacing map
                    </h2>
                    <div className={styles.mapGrid}>
                        {OPERATION_META.map((operation) => {
                            const mapped = product.mapping[operation.value];
                            return (
                                <div key={operation.value} className={styles.mapCard}>
                                    <p className={styles.mapLabel}>{operation.label}</p>
                                    <p className={styles.mapAccount}>
                                        {mapped ? `${mapped.code ?? ''} ${mapped.name ?? ''}`.trim() : 'Not mapped'}
                                    </p>
                                    <p className={styles.mapHint}>{operation.hint}</p>
                                </div>
                            );
                        })}
                    </div>
                </section>

                <section aria-labelledby="terms-heading">
                    <h2 id="terms-heading" className={styles.sectionTitle}>
                        Terms
                    </h2>
                    <dl className={styles.facts}>
                        {termsFacts.map((fact) => (
                            <div key={fact.label} className={styles.fact}>
                                <dt>{fact.label}</dt>
                                <dd>{fact.value}</dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <section aria-labelledby="loans-heading">
                    <div className={styles.sectionHead}>
                        <div>
                            <h2 id="loans-heading" className={styles.sectionTitle}>
                                Recent loans
                            </h2>
                            <p className={styles.count}>{recentLoans.length} most recent loans on this product.</p>
                        </div>
                    </div>
                    <div className={styles.tableWrap}>
                        <SortableDataTable
                            rows={recentLoans}
                            columns={columns}
                            rowKey="id"
                            caption="Recent loans"
                            emptyMessage="No loans booked on this product yet"
                            defaultSort={{ key: 'loan_no', direction: 'desc' }}
                            itemName={{ one: 'loan', other: 'loans' }}
                        />
                    </div>
                </section>

                {editing ? (
                    <ProductFormModal
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setEditing(false);
                            }
                        }}
                        mode="edit"
                        initial={product}
                        companies={[]}
                    />
                ) : null}
            </main>
        </>
    );
}

(ProductShow as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Loan product" section="Loan">
        {page}
    </AppLayout>
);
