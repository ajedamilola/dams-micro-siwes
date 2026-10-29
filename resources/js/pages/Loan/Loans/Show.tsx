import { useMemo, useState, useEffect } from 'react';
import type { ReactNode } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AccountPicker } from '@/components/account-picker/AccountPicker';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { MoneyInput } from '@/components/arc/money-input/money-input';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { AppLayout } from '@/components/layout/AppLayout';
import { useBusinessDate } from '@/lib/business-date';
import { formatNGN } from '@/lib/format';
import { humanizeToken } from '@/lib/loan-customers';
import type { LoanDetail, LoanRepaymentRow, LoanScheduleRow } from '@/types/loan';
import { statusTone } from './components/LoansTable';
import styles from './components/loans.module.css';

type ScheduleRow = LoanScheduleRow & Record<string, unknown>;
type RepaymentRow = LoanRepaymentRow & Record<string, unknown>;

export default function LoanShow({ loan }: { loan: LoanDetail }) {
    const [dialog, setDialog] = useState<'disburse' | 'repay' | null>(null);

    const paidTotal = useMemo(
        () =>
            loan.schedules.reduce(
                (sum, s) =>
                    sum + Number(s.principal_paid) + Number(s.interest_paid) + Number(s.fee_paid) + Number(s.penalty_paid),
                0,
            ),
        [loan.schedules],
    );
    const repayable = Number(loan.total_repayable);
    const progress = repayable > 0 ? Math.min(100, (paidTotal / repayable) * 100) : 0;

    const scheduleRows: ScheduleRow[] = loan.schedules.map((s) => ({ ...s }));
    const repaymentRows: RepaymentRow[] = loan.repayments.map((r) => ({ ...r }));

    const scheduleColumns: DataColumn<ScheduleRow>[] = useMemo(
        () => [
            { key: 'installment_no', label: '#', width: 60 },
            { key: 'due_date', label: 'Due', width: 120 },
            {
                key: 'principal_due',
                label: 'Principal',
                width: 130,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'interest_due',
                label: 'Interest',
                width: 130,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'fee_due',
                label: 'Fee',
                width: 110,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'penalty_due',
                label: 'Penalty',
                width: 110,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'total_due',
                label: 'Total',
                width: 140,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'remaining',
                label: 'Remaining',
                width: 140,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge tone={row.remaining !== undefined && Number(row.remaining) <= 0 ? 'success' : row.status === 'partial' ? 'info' : 'neutral'} size="sm">
                        {Number(row.remaining) <= 0 ? 'paid' : String(value)}
                    </Badge>
                ),
            },
        ],
        [],
    );

    const repaymentColumns: DataColumn<RepaymentRow>[] = useMemo(
        () => [
            { key: 'payment_date', label: 'Date', width: 120 },
            {
                key: 'amount',
                label: 'Amount',
                width: 140,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'principal_component',
                label: 'Principal',
                width: 130,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'interest_component',
                label: 'Interest',
                width: 130,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'fee_component',
                label: 'Fee',
                width: 110,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'penalty_component',
                label: 'Penalty',
                width: 110,
                render: (value) => <span className={styles.code}>{formatNGN(Number(value))}</span>,
            },
            {
                key: 'journal_transaction_id',
                label: 'Journal',
                sortable: false,
                render: (value) =>
                    value ? (
                        <Link href={`/general-ledger/transactions/${String(value)}`} className={styles.crumbLink}>
                            View
                        </Link>
                    ) : (
                        '—'
                    ),
            },
        ],
        [],
    );

    const canDecide = loan.status === 'pending_approval' || loan.status === 'draft';
    const canDisburse = loan.status === 'approved';
    const canRepay = loan.status === 'active';

    return (
        <>
            <Head title={`Loan ${loan.loan_no}`} />
            <main className={styles.page}>
                <nav className={styles.crumbs} aria-label="Breadcrumb">
                    <ol>
                        <li>
                            <Link href="/loans/loans" className={styles.crumbLink}>
                                Loans
                            </Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{loan.loan_no}</li>
                    </ol>
                </nav>

                <div className={styles.header}>
                    <div>
                        <h1 className={styles.title}>{loan.loan_no}</h1>
                        <p className={styles.subtitle}>
                            {loan.customer?.display_name ?? 'Unknown customer'} · {loan.product?.code}{' '}
                            {loan.product?.name ?? ''} · {humanizeToken(loan.interest_method)} ·{' '}
                            {humanizeToken(loan.repayment_frequency)} · {loan.tenor} instalments
                        </p>
                        <div className={styles.badges}>
                            <Badge tone={statusTone(loan.status)} size="sm">
                                {loan.status.replaceAll('_', ' ')}
                            </Badge>
                            {loan.disbursement_journal_id ? (
                                <Link
                                    href={`/general-ledger/transactions/${loan.disbursement_journal_id}`}
                                    className={styles.crumbLink}
                                >
                                    <Badge tone="neutral" size="sm">
                                        Disbursement journal
                                    </Badge>
                                </Link>
                            ) : null}
                        </div>
                    </div>
                    <div className={styles.actions}>
                        {canDecide ? (
                            <>
                                <Button variant="secondary" onClick={() => router.post(`/loans/loans/${loan.id}/reject`)}>
                                    Reject
                                </Button>
                                <Button onClick={() => router.post(`/loans/loans/${loan.id}/approve`)}>Approve</Button>
                            </>
                        ) : null}
                        {canDisburse ? <Button onClick={() => setDialog('disburse')}>Disburse</Button> : null}
                        {canRepay ? (
                            <Button onClick={() => setDialog('repay')}>Record repayment</Button>
                        ) : null}
                    </div>
                </div>

                <div className={styles.stats} role="group" aria-label="Loan amounts">
                    <section className={styles.statCard} aria-label="Principal">
                        <h2 className={styles.statLabel}>Principal</h2>
                        <p className={styles.statValue}>{formatNGN(Number(loan.principal))}</p>
                        <p className={styles.statNote}>Fee {formatNGN(Number(loan.processing_fee))} capitalised.</p>
                    </section>
                    <section className={styles.statCard} aria-label="Interest">
                        <h2 className={styles.statLabel}>Total interest</h2>
                        <p className={styles.statValue}>{formatNGN(Number(loan.total_interest))}</p>
                        <p className={styles.statNote}>
                            {loan.annual_interest_rate}% p.a. {loan.interest_method}.
                        </p>
                    </section>
                    <section className={styles.statCard} aria-label="Repayable">
                        <h2 className={styles.statLabel}>Total repayable</h2>
                        <p className={styles.statValue}>{formatNGN(repayable)}</p>
                        <p className={styles.statNote}>
                            {loan.first_due_date ? `First due ${loan.first_due_date}` : 'No schedule yet'}
                            {loan.maturity_date ? ` · matures ${loan.maturity_date}` : ''}.
                        </p>
                    </section>
                    <section className={styles.statCard} aria-label="Outstanding">
                        <h2 className={styles.statLabel}>Outstanding</h2>
                        <p className={styles.statValue}>{formatNGN(Number(loan.outstanding))}</p>
                        <div
                            className={styles.progress}
                            role="progressbar"
                            aria-valuenow={Math.round(progress)}
                            aria-valuemin={0}
                            aria-valuemax={100}
                        >
                            <div className={styles.progressBar} style={{ width: `${progress}%` }} />
                        </div>
                        <p className={styles.statNote}>{Math.round(progress)}% collected.</p>
                    </section>
                </div>

                <div className={styles.content}>
                    <section aria-labelledby="schedule-heading">
                        <h2 id="schedule-heading" className={styles.sectionTitle}>
                            Repayment schedule
                        </h2>
                        <div className={styles.tableWrap}>
                            <SortableDataTable
                                rows={scheduleRows}
                                columns={scheduleColumns}
                                rowKey="id"
                                caption="Repayment schedule"
                                emptyMessage="No schedule generated"
                                defaultSort={{ key: 'installment_no', direction: 'asc' }}
                                itemName={{ one: 'instalment', other: 'instalments' }}
                            />
                        </div>
                    </section>

                    <section aria-labelledby="repayments-heading">
                        <h2 id="repayments-heading" className={styles.sectionTitle}>
                            Repayments
                        </h2>
                        <div className={styles.tableWrap}>
                            <SortableDataTable
                                rows={repaymentRows}
                                columns={repaymentColumns}
                                rowKey="id"
                                caption="Repayments"
                                emptyMessage="No repayments recorded yet"
                                defaultSort={{ key: 'payment_date', direction: 'desc' }}
                                itemName={{ one: 'repayment', other: 'repayments' }}
                            />
                        </div>
                    </section>
                </div>

                {dialog === 'disburse' ? (
                    <DisburseDialog loanId={loan.id} onClose={() => setDialog(null)} />
                ) : null}
                {dialog === 'repay' ? (
                    <RepayDialog loanId={loan.id} outstanding={loan.outstanding} onClose={() => setDialog(null)} />
                ) : null}
            </main>
        </>
    );
}

function DisburseDialog({ loanId, onClose }: { loanId: string; onClose: () => void }) {
    const businessDate = useBusinessDate();
    const form = useForm({
        bank_account_id: '',
        disbursement_date: businessDate,
    });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent
                title="Disburse loan"
                description="Moves the loan to active and posts Dr customer / Cr bank (+ fee income) to the ledger immediately."
                className={styles.wideDialog}
            >
                <form
                    className={styles.form}
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(`/loans/loans/${loanId}/disburse`, { onSuccess: onClose });
                    }}
                >
                    <div className={styles.formSection}>
                        <AccountPicker
                            label="Settlement account"
                            description="Cash, bank or clearing leaf that funds the disbursement."
                            value={form.data.bank_account_id}
                            onValueChange={(value) => form.setData('bank_account_id', value)}
                            error={form.errors.bank_account_id}
                        />
                        <div className={styles.formGrid}>
                            <Input
                                label="Disbursement date"
                                type="date"
                                max={businessDate}
                                value={form.data.disbursement_date}
                                onChange={(e) => form.setData('disbursement_date', e.target.value)}
                                error={form.errors.disbursement_date}
                            />
                        </div>
                    </div>
                    <div className={styles.formActions}>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" loading={form.processing} disabled={form.processing}>
                            Disburse
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RepayDialog({ loanId, outstanding, onClose }: { loanId: string; outstanding: string; onClose: () => void }) {
    const businessDate = useBusinessDate();
    const form = useForm({
        amount: null as number | null,
        payment_account_id: '',
        payment_date: businessDate,
        notes: '',
    });

    // Set initial amount when component mounts
    useEffect(() => {
        form.setData('amount', Math.round(Number(outstanding) * 100));
    }, [outstanding, form]);

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent
                title="Record repayment"
                description="Allocates penalty, then fee, then interest, then principal across the oldest instalments."
                className={styles.wideDialog}
            >
                <form
                    className={styles.form}
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.transform((payload) => ({
                            ...payload,
                            amount: payload.amount === null ? '' : (payload.amount / 100).toFixed(2),
                        }));
                        form.post(`/loans/loans/${loanId}/repay`, { onSuccess: onClose });
                    }}
                >
                    <div className={styles.formSection}>
                        <div className={styles.formGrid}>
                            <div className={styles.spanFull}>
                                <MoneyInput
                                    label="Amount (₦)"
                                    value={form.data.amount}
                                    onValueChange={(value) => form.setData('amount', value)}
                                    currencies={['NGN']}
                                    currency="NGN"
                                    locale="en-NG"
                                    quickAdd={[]}
                                    error={form.errors.amount}
                                    description="Amount in naira."
                                />
                            </div>
                            <Input
                                label="Payment date"
                                type="date"
                                max={businessDate}
                                value={form.data.payment_date}
                                onChange={(e) => form.setData('payment_date', e.target.value)}
                                error={form.errors.payment_date}
                            />
                        </div>
                        <AccountPicker
                            label="Received into"
                            description="Cash, bank or clearing leaf that receives the repayment."
                            value={form.data.payment_account_id}
                            onValueChange={(value) => form.setData('payment_account_id', value)}
                            error={form.errors.payment_account_id}
                        />
                        <Input
                            label="Notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            error={form.errors.notes}
                            autoComplete="off"
                        />
                    </div>
                    <p className={styles.formSectionNote}>Outstanding balance is {formatNGN(Number(outstanding))}.</p>
                    <div className={styles.formActions}>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" loading={form.processing} disabled={form.processing}>
                            Record repayment
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

(LoanShow as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Loan" section="Loan">
        {page}
    </AppLayout>
);
