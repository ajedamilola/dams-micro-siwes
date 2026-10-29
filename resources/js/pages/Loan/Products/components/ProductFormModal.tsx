import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AccountPicker } from '@/components/account-picker/AccountPicker';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { MoneyInput } from '@/components/arc/money-input/money-input';
import { Select } from '@/components/arc/select/select';
import { Stepper } from '@/components/arc/stepper/stepper';
import { Textarea } from '@/components/arc/textarea/textarea';
import type { CompanyOption } from '@/types/ledger';
import type { LoanProduct, OperationType } from '@/types/loan';
import { OPERATION_META } from '@/types/loan';
import styles from './products.module.css';

interface ProductFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'create' | 'edit';
    initial: LoanProduct | null;
    companies: CompanyOption[];
}

const METHOD_OPTIONS = [
    { value: 'flat', label: 'Flat — interest on the full principal' },
    { value: 'reducing', label: 'Reducing — interest on outstanding balance' },
];

const FREQUENCY_OPTIONS = [
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Bi-weekly' },
    { value: 'monthly', label: 'Monthly' },
];

const STATUS_OPTIONS = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const STEPS = [
    { id: 'basics', label: 'Basics', description: 'Code, name and interest' },
    { id: 'terms', label: 'Terms', description: 'Limits and charges' },
    { id: 'mapping', label: 'Interfacing map', description: 'Optional now, editable later' },
];

const FIELD_STEP: Record<string, number> = {
    company_id: 0,
    code: 0,
    name: 0,
    description: 0,
    interest_method: 0,
    annual_interest_rate: 0,
    repayment_frequency: 1,
    min_principal: 1,
    max_principal: 1,
    min_tenor: 1,
    max_tenor: 1,
    processing_fee_rate: 1,
    penalty_rate_daily: 1,
    penalty_grace_days: 1,
    status: 1,
    mapping: 2,
};

const MAPPING_OPERATIONS = OPERATION_META.map((operation) => operation.value);

function toKobo(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined) {
        return null;
    }
    const amount = Number(value);
    return Number.isFinite(amount) ? Math.round(amount * 100) : null;
}

function toNumber(value: string): number | null {
    const amount = Number(value);
    return value.trim() === '' || !Number.isFinite(amount) ? null : amount;
}

export function ProductFormModal({ open, onOpenChange, mode, initial, companies }: ProductFormModalProps) {
    const [step, setStep] = useState(0);
    const [stepError, setStepError] = useState<string | null>(null);

    const form = useForm({
        company_id: initial ? String(initial.company_id) : (companies[0]?.id ? String(companies[0].id) : ''),
        code: initial?.code ?? '',
        name: initial?.name ?? '',
        description: initial?.description ?? '',
        interest_method: initial?.interest_method ?? 'flat',
        annual_interest_rate: initial?.annual_interest_rate ?? '36',
        repayment_frequency: initial?.repayment_frequency ?? 'monthly',
        min_principal: initial ? toKobo(initial.min_principal) : 1000000,
        max_principal: initial ? toKobo(initial.max_principal) : 100000000,
        min_tenor: String(initial?.min_tenor ?? 1),
        max_tenor: String(initial?.max_tenor ?? 12),
        processing_fee_rate: initial?.processing_fee_rate ?? '2',
        penalty_rate_daily: initial?.penalty_rate_daily ?? '0.1',
        penalty_grace_days: String(initial?.penalty_grace_days ?? 0),
        status: initial?.status ?? 'active',
        mapping_principal_receivable: initial?.mapping['principal_receivable']?.account_id ?? '',
        mapping_interest_receivable: initial?.mapping['interest_receivable']?.account_id ?? '',
        mapping_interest_income: initial?.mapping['interest_income']?.account_id ?? '',
        mapping_fee_income: initial?.mapping['fee_income']?.account_id ?? '',
        mapping_penalty_receivable: initial?.mapping['penalty_receivable']?.account_id ?? '',
        mapping_penalty_income: initial?.mapping['penalty_income']?.account_id ?? '',
    });

    const data = form.data;
    const isCreate = mode === 'create';

    // Jump to the first step that holds a backend validation error.
    useEffect(() => {
        const keys = Object.keys(form.errors);
        if (keys.length === 0) {
            return;
        }
        const steps = keys.map((key) => FIELD_STEP[key] ?? FIELD_STEP[key.split('.')[0] ?? ''] ?? 0);
        setStep(Math.min(...steps));
    }, [form.errors]);

    function setMapping(operation: OperationType, accountId: string): void {
        form.setData(`mapping_${operation}` as keyof typeof data, accountId);
    }

    function mappingError(operation: OperationType): string | undefined {
        return form.errors[`mapping.${operation}` as keyof typeof form.errors] as string | undefined;
    }

    function validateStep(index: number): boolean {
        if (index === 0) {
            if (isCreate && !data.company_id) {
                setStepError('Select a company to continue.');
                return false;
            }
            if (isCreate && !data.code.trim()) {
                setStepError('Enter a product code to continue.');
                return false;
            }
            if (!data.name.trim()) {
                setStepError('Enter a product name to continue.');
                return false;
            }
            const rate = toNumber(data.annual_interest_rate);
            if (rate === null || rate < 0 || rate > 500) {
                setStepError('Annual interest rate must be between 0 and 500.');
                return false;
            }
        }

        if (index === 1) {
            if (data.min_principal === null || data.max_principal === null) {
                setStepError('Enter both the minimum and maximum principal.');
                return false;
            }
            if (data.max_principal < data.min_principal) {
                setStepError('Maximum principal must be at least the minimum principal.');
                return false;
            }
            const minTenor = toNumber(data.min_tenor);
            const maxTenor = toNumber(data.max_tenor);
            if (minTenor === null || maxTenor === null || minTenor < 1 || maxTenor < 1) {
                setStepError('Tenor must be at least one instalment.');
                return false;
            }
            if (maxTenor < minTenor) {
                setStepError('Maximum tenor must be at least the minimum tenor.');
                return false;
            }
            if (toNumber(data.processing_fee_rate) === null) {
                setStepError('Processing fee must be a number.');
                return false;
            }
            if (toNumber(data.penalty_rate_daily) === null) {
                setStepError('Penalty rate must be a number.');
                return false;
            }
            if (toNumber(data.penalty_grace_days) === null) {
                setStepError('Penalty grace days must be a number.');
                return false;
            }
        }

        if (index === 2) {
            const mapped = MAPPING_OPERATIONS.filter(
                (operation) => data[`mapping_${operation}` as keyof typeof data] !== '',
            );
            if (mapped.length > 0 && mapped.length < MAPPING_OPERATIONS.length) {
                setStepError('Map all six accounts, or leave every field blank to finish later.');
                return false;
            }
        }

        setStepError(null);
        return true;
    }

    function goNext(): void {
        if (validateStep(step)) {
            setStep((current) => Math.min(current + 1, STEPS.length - 1));
        }
    }

    function goBack(): void {
        setStepError(null);
        setStep((current) => Math.max(current - 1, 0));
    }

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        for (const index of [0, 1, 2]) {
            if (!validateStep(index)) {
                setStep(index);
                return;
            }
        }

        form.transform((payload) => {
            const mapping: Record<string, string> = {};
            for (const operation of MAPPING_OPERATIONS) {
                const accountId = payload[`mapping_${operation}` as keyof typeof payload] as string;
                if (accountId) {
                    mapping[operation] = accountId;
                }
            }

            return {
                ...payload,
                min_principal: payload.min_principal === null ? '' : String(payload.min_principal / 100),
                max_principal: payload.max_principal === null ? '' : String(payload.max_principal / 100),
                mapping,
            };
        });

        if (mode === 'create') {
            form.post('/loans/products', {
                onSuccess: () => onOpenChange(false),
            });
        } else if (initial) {
            form.put(`/loans/products/${initial.id}`, {
                onSuccess: () => onOpenChange(false),
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={isCreate ? 'New loan product' : `Edit ${initial?.code ?? 'product'}`}
                description={
                    isCreate
                        ? 'Define the terms once. Every loan booked on this product inherits them, and every posting follows the interfacing map.'
                        : 'Terms apply to future loans. Existing loans keep the terms they were booked with.'
                }
                className={styles.wideDialog}
            >
                <div className={styles.stepperWrap}>
                    <Stepper
                        steps={STEPS}
                        current={step}
                        onStepSelect={(index) => {
                            if (index < step) {
                                setStepError(null);
                                setStep(index);
                            }
                        }}
                    />
                </div>
                <form className={styles.form} onSubmit={handleSubmit} noValidate>
                    {stepError ? (
                        <p className={styles.formError} role="alert">
                            {stepError}
                        </p>
                    ) : null}

                    {step === 0 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Basics</h3>
                                <div className={styles.formGrid}>
                                    {isCreate ? (
                                        <Select
                                            label="Company"
                                            value={data.company_id}
                                            onValueChange={(value) => form.setData('company_id', value)}
                                            options={companies.map((company) => ({
                                                value: String(company.id),
                                                label: `${company.code} ${company.name}`,
                                            }))}
                                            description={form.errors.company_id}
                                        />
                                    ) : null}
                                    {isCreate ? (
                                        <Input
                                            label="Product code"
                                            value={data.code}
                                            onChange={(event) => form.setData('code', event.target.value)}
                                            error={form.errors.code}
                                            autoComplete="off"
                                        />
                                    ) : null}
                                    <Input
                                        label="Product name"
                                        value={data.name}
                                        onChange={(event) => form.setData('name', event.target.value)}
                                        error={form.errors.name}
                                        autoComplete="off"
                                    />
                                    {isCreate ? (
                                        <Select
                                            label="Interest method"
                                            value={data.interest_method}
                                            onValueChange={(value) =>
                                                form.setData('interest_method', value as 'flat' | 'reducing')
                                            }
                                            options={METHOD_OPTIONS}
                                            description={form.errors.interest_method}
                                        />
                                    ) : null}
                                    {isCreate ? (
                                        <Select
                                            label="Repayment frequency"
                                            value={data.repayment_frequency}
                                            onValueChange={(value) =>
                                                form.setData('repayment_frequency', value as 'weekly' | 'biweekly' | 'monthly')
                                            }
                                            options={FREQUENCY_OPTIONS}
                                            description={form.errors.repayment_frequency}
                                        />
                                    ) : null}
                                    <Input
                                        label="Annual interest rate (%)"
                                        value={data.annual_interest_rate}
                                        onChange={(event) => form.setData('annual_interest_rate', event.target.value)}
                                        error={form.errors.annual_interest_rate}
                                        autoComplete="off"
                                    />
                                </div>
                                <Textarea
                                    label="Description"
                                    rows={2}
                                    value={data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    error={form.errors.description}
                                />
                            </div>
                        </div>
                    ) : null}

                    {step === 1 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Limits and charges</h3>
                                <p className={styles.formSectionNote}>
                                    The processing fee is capitalised at disbursement; the penalty accrues daily on
                                    overdue instalments after the grace period.
                                </p>
                                <div className={styles.formGrid}>
                                    <MoneyInput
                                        label="Minimum principal (₦)"
                                        value={data.min_principal}
                                        onValueChange={(value) => form.setData('min_principal', value)}
                                        currencies={['NGN']}
                                        currency="NGN"
                                        locale="en-NG"
                                        quickAdd={[]}
                                        error={form.errors.min_principal}
                                        description="Amount in naira."
                                    />
                                    <MoneyInput
                                        label="Maximum principal (₦)"
                                        value={data.max_principal}
                                        onValueChange={(value) => form.setData('max_principal', value)}
                                        currencies={['NGN']}
                                        currency="NGN"
                                        locale="en-NG"
                                        quickAdd={[]}
                                        error={form.errors.max_principal}
                                        description="Amount in naira."
                                    />
                                    <Input
                                        label="Minimum tenor (instalments)"
                                        value={data.min_tenor}
                                        onChange={(event) => form.setData('min_tenor', event.target.value)}
                                        error={form.errors.min_tenor}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Maximum tenor (instalments)"
                                        value={data.max_tenor}
                                        onChange={(event) => form.setData('max_tenor', event.target.value)}
                                        error={form.errors.max_tenor}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Processing fee (% of principal)"
                                        value={data.processing_fee_rate}
                                        onChange={(event) => form.setData('processing_fee_rate', event.target.value)}
                                        error={form.errors.processing_fee_rate}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Penalty rate (% per day)"
                                        value={data.penalty_rate_daily}
                                        onChange={(event) => form.setData('penalty_rate_daily', event.target.value)}
                                        error={form.errors.penalty_rate_daily}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Penalty grace days"
                                        value={data.penalty_grace_days}
                                        onChange={(event) => form.setData('penalty_grace_days', event.target.value)}
                                        error={form.errors.penalty_grace_days}
                                        autoComplete="off"
                                    />
                                    {!isCreate ? (
                                        <Select
                                            label="Status"
                                            value={data.status}
                                            onValueChange={(value) => form.setData('status', value as 'active' | 'inactive')}
                                            options={STATUS_OPTIONS}
                                            description={form.errors.status}
                                        />
                                    ) : null}
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {step === 2 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Interfacing map</h3>
                                <p className={styles.formSectionNote}>
                                    Optional. Map all six accounts now, or leave every field blank and finish the
                                    map later from this product. Only active Master Chart leaves are accepted.
                                </p>
                                <div className={styles.formGrid}>
                                    {OPERATION_META.map((operation) => (
                                        <AccountPicker
                                            key={operation.value}
                                            label={operation.label}
                                            description={operation.hint}
                                            value={data[`mapping_${operation.value}` as keyof typeof data] as string}
                                            onValueChange={(value) => setMapping(operation.value, value)}
                                            error={mappingError(operation.value)}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <div className={styles.wizardNav}>
                        <div className={styles.wizardBack}>
                            {step > 0 ? (
                                <Button type="button" variant="secondary" onClick={goBack}>
                                    Back
                                </Button>
                            ) : (
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                            )}
                        </div>
                        {step < STEPS.length - 1 ? (
                            <Button
                                type="button"
                                onClick={(event) => {
                                    event.preventDefault();
                                    goNext();
                                }}
                            >
                                Continue
                            </Button>
                        ) : (
                            <Button type="submit" loading={form.processing} disabled={form.processing}>
                                {isCreate ? 'Create product' : 'Save changes'}
                            </Button>
                        )}
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
