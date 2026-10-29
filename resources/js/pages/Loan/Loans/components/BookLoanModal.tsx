import { useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { MoneyInput } from '@/components/arc/money-input/money-input';
import { Select } from '@/components/arc/select/select';
import { formatNGN } from '@/lib/format';
import { humanizeToken } from '@/lib/loan-customers';
import type { ProductOption } from '@/types/loan';
import { CustomerPicker } from './CustomerPicker';
import styles from './loans.module.css';

interface BookLoanModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    products: ProductOption[];
}

const PERIODS_PER_YEAR: Record<string, number> = { weekly: 52, biweekly: 26, monthly: 12 };

function previewTotals(principal: number, annualRate: number, method: string, frequency: string, tenor: number, feeRate: number) {
    if (!(principal > 0) || !(tenor > 0)) {
        return null;
    }
    const fee = (principal * feeRate) / 100;
    const periods = PERIODS_PER_YEAR[frequency] ?? 12;
    const periodic = annualRate / 100 / periods;
    let interest: number;
    if (method === 'flat' || periodic <= 0) {
        interest = principal * periodic * tenor;
    } else {
        const factor = Math.pow(1 + periodic, tenor);
        const emi = (principal * periodic * factor) / (factor - 1);
        let balance = principal;
        interest = 0;
        for (let i = 0; i < tenor; i += 1) {
            const part = balance * periodic;
            interest += part;
            balance -= emi - part;
        }
    }
    return { fee, interest, repayable: principal + fee + interest };
}

export function BookLoanModal({ open, onOpenChange, products }: BookLoanModalProps) {
    const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    const form = useForm({
        loan_product_id: products[0]?.id ?? '',
        loan_customer_id: '',
        principal: 10000000 as number | null, // 100000.00 NGN in minor units (kobo)
        tenor: '6',
        first_due_date: '',
        notes: '',
    });

    const selected = products.find((product) => product.id === form.data.loan_product_id) ?? products[0] ?? null;

    const preview = useMemo(() => {
        if (!selected) {
            return null;
        }
        return previewTotals(
            form.data.principal === null ? 0 : form.data.principal / 100,
            Number(selected.annual_interest_rate),
            selected.interest_method,
            selected.repayment_frequency,
            Number(form.data.tenor),
            Number(selected.processing_fee_rate),
        );
    }, [form.data.principal, form.data.tenor, selected]);

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        form.transform((payload) => ({
            ...payload,
            principal: payload.principal === null ? '' : (payload.principal / 100).toFixed(2),
        }));
        form.post('/loans/loans', {
            onSuccess: () => onOpenChange(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title="Book a loan"
                description="Applies for review. Approval, disbursement and repayments happen on the loan page."
                className={styles.wideDialog}
            >
                <form className={styles.form} onSubmit={handleSubmit} noValidate>
                    <div className={styles.formSection}>
                        <h3 className={styles.formSectionTitle}>Application</h3>
                        <div className={styles.formGrid}>
                            <div className={styles.spanFull}>
                                <Select
                                    label="Product"
                                    value={form.data.loan_product_id}
                                    onValueChange={(value) => form.setData('loan_product_id', value)}
                                    options={products.map((product) => ({
                                        value: product.id,
                                        label: `${product.code} ${product.name} · ${product.company_name ?? ''}`,
                                    }))}
                                    description={form.errors.loan_product_id}
                                />
                            </div>
                            <div className={styles.spanFull}>
                                <CustomerPicker
                                    value={form.data.loan_customer_id}
                                    onValueChange={(value) => form.setData('loan_customer_id', value)}
                                    error={form.errors.loan_customer_id}
                                />
                            </div>
                            <div className={styles.spanFull}>
                                <MoneyInput
                                    label="Principal (₦)"
                                    value={form.data.principal}
                                    onValueChange={(value) => form.setData('principal', value)}
                                    currencies={['NGN']}
                                    currency="NGN"
                                    locale="en-NG"
                                    quickAdd={[]}
                                    error={form.errors.principal}
                                    description="Amount in naira."
                                />
                            </div>
                            <Input
                                label="Tenor (instalments)"
                                value={form.data.tenor}
                                onChange={(event) => form.setData('tenor', event.target.value)}
                                error={form.errors.tenor}
                                autoComplete="off"
                            />
                            <Input
                                label="First due date"
                                type="date"
                                value={form.data.first_due_date}
                                onChange={(event) => form.setData('first_due_date', event.target.value)}
                                error={form.errors.first_due_date}
                            />
                        </div>
                        {selected ? (
                            <p className={styles.formSectionNote}>
                                {selected.code} · {humanizeToken(selected.interest_method)} ·{' '}
                                {selected.annual_interest_rate}% p.a. · {humanizeToken(selected.repayment_frequency)} ·
                                limits {formatNGN(Number(selected.min_principal))} –{' '}
                                {formatNGN(Number(selected.max_principal))}, tenor {selected.min_tenor}–
                                {selected.max_tenor}
                            </p>
                        ) : (
                            <p className={styles.formSectionNote}>No active products. Create one first.</p>
                        )}
                    </div>

                    {preview ? (
                        <div className={styles.preview} aria-live="polite">
                            <span>
                                Processing fee <strong>{formatNGN(preview.fee)}</strong> · Total interest{' '}
                                <strong>{formatNGN(preview.interest)}</strong>
                            </span>
                            <span>
                                Total repayable <strong>{formatNGN(preview.repayable)}</strong> over{' '}
                                {form.data.tenor} {selected?.repayment_frequency} instalments
                            </span>
                        </div>
                    ) : null}
                    {form.errors.first_due_date && !preview ? (
                        <p className={styles.formError} role="alert">
                            Pick a first due date after {tomorrow}.
                        </p>
                    ) : null}

                    <div className={styles.formActions}>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" loading={form.processing} disabled={form.processing || !selected}>
                            Submit application
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
