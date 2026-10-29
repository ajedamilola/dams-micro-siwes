import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { MoneyInput } from '@/components/arc/money-input/money-input';
import { Select } from '@/components/arc/select/select';
import { Stepper } from '@/components/arc/stepper/stepper';
import { Textarea } from '@/components/arc/textarea/textarea';
import { addDays, useBusinessDate } from '@/lib/business-date';
import { customerUrl } from '@/lib/loan-customers';
import type { CompanyOption } from '@/types/ledger';
import type { LoanCustomer } from '@/types/loan-customer';
import styles from './customers.module.css';

interface CustomerFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'create' | 'edit';
    initial: LoanCustomer | null;
    companies: CompanyOption[];
}

const TYPE_OPTIONS = [
    { value: 'individual', label: 'Individual' },
    { value: 'corporate', label: 'Corporate' },
    { value: 'group', label: 'Group' },
];

const GENDER_OPTIONS = [
    { value: '', label: 'Select gender' },
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];

const MARITAL_OPTIONS = [
    { value: '', label: 'Select status' },
    { value: 'single', label: 'Single' },
    { value: 'married', label: 'Married' },
    { value: 'divorced', label: 'Divorced' },
    { value: 'widowed', label: 'Widowed' },
];

const TIER_OPTIONS = [
    { value: 'tier1', label: 'Tier 1 - Low value' },
    { value: 'tier2', label: 'Tier 2 - Medium value' },
    { value: 'tier3', label: 'Tier 3 - Full KYC' },
];

const ID_OPTIONS = [
    { value: '', label: 'Select ID type' },
    { value: 'national_id', label: 'National ID' },
    { value: 'drivers_license', label: 'Driver licence' },
    { value: 'intl_passport', label: 'International passport' },
    { value: 'voters_card', label: 'Voter card' },
    { value: 'cac_certificate', label: 'CAC certificate' },
];

const POA_OPTIONS = [
    { value: '', label: 'Select proof of address' },
    { value: 'utility_bill', label: 'Utility bill' },
    { value: 'bank_statement', label: 'Bank statement' },
    { value: 'tenancy_agreement', label: 'Tenancy agreement' },
    { value: 'local_govt_letter', label: 'Local government letter' },
    { value: 'employer_letter', label: 'Employer letter' },
];

const EMPLOYMENT_OPTIONS = [
    { value: '', label: 'Select employment status' },
    { value: 'employed', label: 'Employed' },
    { value: 'self_employed', label: 'Self employed' },
    { value: 'unemployed', label: 'Unemployed' },
    { value: 'student', label: 'Student' },
    { value: 'retired', label: 'Retired' },
];

const STATUS_OPTIONS = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'blacklisted', label: 'Blacklisted' },
];

const STEPS = [
    { id: 'type', label: 'Account and type', description: 'Company and customer type' },
    { id: 'personal', label: 'Personal', description: 'Names and birth details' },
    { id: 'contact', label: 'Contact', description: 'Phone and address' },
    { id: 'kyc', label: 'KYC', description: 'BVN, NIN and IDs' },
    { id: 'work', label: 'Work and finish', description: 'Job, next of kin and review' },
];

/** Backend field to wizard step, used to jump to the first failing step. */
const FIELD_STEP: Record<string, number> = {
    company_id: 0,
    customer_type: 0,
    status: 0,
    first_name: 1,
    middle_name: 1,
    last_name: 1,
    business_name: 1,
    rc_number: 1,
    gender: 1,
    date_of_birth: 1,
    marital_status: 1,
    phone: 2,
    phone_alt: 2,
    email: 2,
    address: 2,
    city: 2,
    state: 2,
    lga: 2,
    nationality: 2,
    bvn: 3,
    nin: 3,
    means_of_id_type: 3,
    means_of_id_number: 3,
    id_expiry_date: 3,
    proof_of_address_type: 3,
    kyc_tier: 3,
    employment_status: 4,
    employer_name: 4,
    monthly_income: 4,
    next_of_kin_name: 4,
    next_of_kin_relationship: 4,
    next_of_kin_phone: 4,
    notes: 4,
};

const PHONE_RE = /^(0[789][01]\d{8}|\+234[789][01]\d{8})$/;

function toKobo(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined) return null;
    const amount = Number(value);
    return Number.isFinite(amount) ? Math.round(amount * 100) : null;
}

export function CustomerFormModal({ open, onOpenChange, mode, initial, companies }: CustomerFormModalProps) {
    const businessDate = useBusinessDate();
    const idExpiryFloor = addDays(businessDate, 1);
    const [step, setStep] = useState(0);
    const [stepError, setStepError] = useState<string | null>(null);

    const form = useForm({
        company_id: initial?.company_id ? String(initial.company_id) : (companies[0]?.id ? String(companies[0].id) : ''),
        customer_type: initial?.customer_type ?? 'individual',
        first_name: initial?.first_name ?? '',
        middle_name: initial?.middle_name ?? '',
        last_name: initial?.last_name ?? '',
        business_name: initial?.business_name ?? '',
        rc_number: initial?.rc_number ?? '',
        gender: initial?.gender ?? '',
        date_of_birth: initial?.date_of_birth ?? '',
        marital_status: initial?.marital_status ?? '',
        phone: initial?.phone ?? '',
        phone_alt: initial?.phone_alt ?? '',
        email: initial?.email ?? '',
        address: initial?.address ?? '',
        city: initial?.city ?? '',
        state: initial?.state ?? '',
        lga: initial?.lga ?? '',
        nationality: initial?.nationality ?? 'Nigeria',
        bvn: initial?.bvn ?? '',
        nin: initial?.nin ?? '',
        means_of_id_type: initial?.means_of_id_type ?? '',
        means_of_id_number: initial?.means_of_id_number ?? '',
        id_expiry_date: initial?.id_expiry_date ?? '',
        proof_of_address_type: initial?.proof_of_address_type ?? '',
        kyc_tier: initial?.kyc_tier ?? 'tier3',
        risk_rating: initial?.risk_rating ?? 'low',
        is_pep: initial?.is_pep ?? false,
        employment_status: initial?.employment_status ?? '',
        employer_name: initial?.employer_name ?? '',
        monthly_income: initial ? toKobo(initial.monthly_income) : null,
        next_of_kin_name: initial?.next_of_kin_name ?? '',
        next_of_kin_relationship: initial?.next_of_kin_relationship ?? '',
        next_of_kin_phone: initial?.next_of_kin_phone ?? '',
        status: initial?.status ?? 'active',
        notes: initial?.notes ?? '',
        gl_mode: 'auto',
    });

    const isCorporate = form.data.customer_type === 'corporate';
    const data = form.data;

    // Jump to the first step that holds a backend validation error.
    useEffect(() => {
        const keys = Object.keys(form.errors);
        if (keys.length === 0) {
            return;
        }
        const steps = keys.map((key) => FIELD_STEP[key] ?? 4);
        setStep(Math.min(...steps));
    }, [form.errors]);

    function emptyToNull(value: string): string | null {
        return value.trim() === '' ? null : value.trim();
    }

    function validateStep(index: number): boolean {
        if (index === 0) {
            if (!data.company_id) {
                setStepError('Select a company to continue.');
                return false;
            }
            if (!data.customer_type) {
                setStepError('Select a customer type to continue.');
                return false;
            }
        }
        if (index === 1) {
            if (!data.first_name.trim() || !data.last_name.trim()) {
                setStepError('First name and last name are required.');
                return false;
            }
            if (isCorporate && (!data.business_name.trim() || !data.rc_number.trim())) {
                setStepError('Corporate customers need a business name and RC number.');
                return false;
            }
        }
        if (index === 2) {
            if (!data.phone.trim() || !PHONE_RE.test(data.phone.trim())) {
                setStepError('Enter a reachable Nigerian phone number.');
                return false;
            }
            if (!data.address.trim()) {
                setStepError('Enter the customer address to continue.');
                return false;
            }
        }
        if (index === 3) {
            const tier = data.kyc_tier;
            const bvn = data.bvn.trim();
            const nin = data.nin.trim();
            const eleven = /^\d{11}$/;
            if (tier === 'tier1' && !bvn && !nin) {
                setStepError('Tier 1 needs a BVN or a NIN.');
                return false;
            }
            if ((tier === 'tier2' || tier === 'tier3') && (!eleven.test(bvn) || !eleven.test(nin))) {
                setStepError('Tier 2 and Tier 3 need an 11-digit BVN and an 11-digit NIN.');
                return false;
            }
            if (tier === 'tier3' && (!data.means_of_id_type || !data.means_of_id_number.trim())) {
                setStepError('Tier 3 needs a valid means of ID and its number.');
                return false;
            }
            if (tier === 'tier3' && !data.proof_of_address_type) {
                setStepError('Tier 3 needs a verified proof of address.');
                return false;
            }
        }
        if (index === 4) {
            if (data.monthly_income !== null && Number.isNaN(data.monthly_income / 100)) {
                setStepError('Monthly income must be a number.');
                return false;
            }
            if (data.next_of_kin_phone.trim() && !PHONE_RE.test(data.next_of_kin_phone.trim())) {
                setStepError('Next of kin phone must be a Nigerian number.');
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
        if (!validateStep(0) || !validateStep(1) || !validateStep(2) || !validateStep(3) || !validateStep(4)) {
            const checks = [0, 1, 2, 3, 4];
            for (const index of checks) {
                const snapshotError = stepError;
                void snapshotError;
                if (!validateStep(index)) {
                    setStep(index);
                    break;
                }
            }
            return;
        }
        form.transform((payload) => ({
            ...payload,
            middle_name: emptyToNull(payload.middle_name),
            business_name: emptyToNull(payload.business_name),
            rc_number: emptyToNull(payload.rc_number),
            gender: emptyToNull(payload.gender),
            date_of_birth: emptyToNull(payload.date_of_birth),
            marital_status: emptyToNull(payload.marital_status),
            phone_alt: emptyToNull(payload.phone_alt),
            email: emptyToNull(payload.email),
            city: emptyToNull(payload.city),
            state: emptyToNull(payload.state),
            lga: emptyToNull(payload.lga),
            bvn: emptyToNull(payload.bvn),
            nin: emptyToNull(payload.nin),
            means_of_id_type: emptyToNull(payload.means_of_id_type),
            means_of_id_number: emptyToNull(payload.means_of_id_number),
            id_expiry_date: emptyToNull(payload.id_expiry_date),
            proof_of_address_type: emptyToNull(payload.proof_of_address_type),
            employment_status: emptyToNull(payload.employment_status),
            employer_name: emptyToNull(payload.employer_name),
            monthly_income: payload.monthly_income === null ? null : payload.monthly_income / 100,
            next_of_kin_name: emptyToNull(payload.next_of_kin_name),
            next_of_kin_relationship: emptyToNull(payload.next_of_kin_relationship),
            next_of_kin_phone: emptyToNull(payload.next_of_kin_phone),
            notes: emptyToNull(payload.notes),
        }));
        if (mode === 'create') {
            form.post('/loans/customers', {
                onSuccess: () => onOpenChange(false),
            });
        } else if (initial) {
            form.put(customerUrl(initial.id), {
                onSuccess: () => onOpenChange(false),
            });
        }
    }

    const displayName =
        data.customer_type === 'corporate' || data.customer_type === 'group'
            ? data.business_name.trim() || `${data.first_name} ${data.last_name}`.trim()
            : `${data.first_name} ${data.middle_name ? `${data.middle_name} ` : ''}${data.last_name}`.trim();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={mode === 'create' ? 'New customer' : `Edit ${initial?.display_name ?? 'customer'}`}
                description={
                    mode === 'create'
                        ? 'Creates the customer and an account in the Customers subledger.'
                        : 'Updates the customer and keeps the linked account name in sync.'
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
                                <h3 className={styles.formSectionTitle}>Account and type</h3>
                                <p className={styles.formSectionNote}>
                                    The account is created automatically in the Customers subledger.
                                </p>
                                <div className={styles.formGrid}>
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
                                    <Select
                                        label="Customer type"
                                        value={data.customer_type}
                                        onValueChange={(value) =>
                                            form.setData('customer_type', value as 'individual' | 'corporate' | 'group')
                                        }
                                        options={TYPE_OPTIONS}
                                        description={form.errors.customer_type}
                                    />
                                    <Select
                                        label="Status"
                                        value={data.status}
                                        onValueChange={(value) => form.setData('status', value as 'active' | 'inactive' | 'blacklisted')}
                                        options={STATUS_OPTIONS}
                                        description={form.errors.status}
                                    />
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {step === 1 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Personal details</h3>
                                <div className={styles.formGrid}>
                                    <Input
                                        label="First name"
                                        value={data.first_name}
                                        onChange={(event) => form.setData('first_name', event.target.value)}
                                        error={form.errors.first_name}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Middle name"
                                        value={data.middle_name}
                                        onChange={(event) => form.setData('middle_name', event.target.value)}
                                        error={form.errors.middle_name}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Last name"
                                        value={data.last_name}
                                        onChange={(event) => form.setData('last_name', event.target.value)}
                                        error={form.errors.last_name}
                                        autoComplete="off"
                                    />
                                    <Select
                                        label="Gender"
                                        value={data.gender}
                                        onValueChange={(value) => form.setData('gender', value)}
                                        options={GENDER_OPTIONS}
                                        description={form.errors.gender}
                                    />
                                    <Input
                                        label="Date of birth"
                                        type="date"
                                        max={businessDate}
                                        value={data.date_of_birth}
                                        onChange={(event) => form.setData('date_of_birth', event.target.value)}
                                        error={form.errors.date_of_birth}
                                    />
                                    <Select
                                        label="Marital status"
                                        value={data.marital_status}
                                        onValueChange={(value) => form.setData('marital_status', value)}
                                        options={MARITAL_OPTIONS}
                                        description={form.errors.marital_status}
                                    />
                                </div>
                                {isCorporate ? (
                                    <div className={styles.formGrid}>
                                        <Input
                                            label="Business name"
                                            value={data.business_name}
                                            onChange={(event) => form.setData('business_name', event.target.value)}
                                            error={form.errors.business_name}
                                            autoComplete="off"
                                        />
                                        <Input
                                            label="RC number"
                                            value={data.rc_number}
                                            onChange={(event) => form.setData('rc_number', event.target.value)}
                                            error={form.errors.rc_number}
                                            autoComplete="off"
                                        />
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    ) : null}

                    {step === 2 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Contact and address</h3>
                                <p className={styles.formSectionNote}>
                                    Use a reachable Nigerian phone number starting with 070, 080, 081, 090 or +234.
                                </p>
                                <div className={styles.formGrid}>
                                    <Input
                                        label="Phone"
                                        value={data.phone}
                                        onChange={(event) => form.setData('phone', event.target.value)}
                                        error={form.errors.phone}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Alt phone"
                                        value={data.phone_alt}
                                        onChange={(event) => form.setData('phone_alt', event.target.value)}
                                        error={form.errors.phone_alt}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Email"
                                        value={data.email}
                                        onChange={(event) => form.setData('email', event.target.value)}
                                        error={form.errors.email}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Nationality"
                                        value={data.nationality}
                                        onChange={(event) => form.setData('nationality', event.target.value)}
                                        error={form.errors.nationality}
                                        autoComplete="off"
                                    />
                                </div>
                                <Textarea
                                    label="Address"
                                    rows={2}
                                    value={data.address}
                                    onChange={(event) => form.setData('address', event.target.value)}
                                    error={form.errors.address}
                                />
                                <div className={styles.formGrid}>
                                    <Input
                                        label="City"
                                        value={data.city}
                                        onChange={(event) => form.setData('city', event.target.value)}
                                        error={form.errors.city}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="State"
                                        value={data.state}
                                        onChange={(event) => form.setData('state', event.target.value)}
                                        error={form.errors.state}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="LGA"
                                        value={data.lga}
                                        onChange={(event) => form.setData('lga', event.target.value)}
                                        error={form.errors.lga}
                                        autoComplete="off"
                                    />
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {step === 3 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>KYC and identity</h3>
                                <p className={styles.formSectionNote}>
                                    Tier 1 needs BVN or NIN. Tier 2 and Tier 3 need both BVN and NIN. Tier 3 also needs an ID and proof of address.
                                </p>
                                <div className={styles.formGrid}>
                                    <Input
                                        label="BVN (11 digits)"
                                        value={data.bvn}
                                        onChange={(event) => form.setData('bvn', event.target.value)}
                                        error={form.errors.bvn}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="NIN (11 digits)"
                                        value={data.nin}
                                        onChange={(event) => form.setData('nin', event.target.value)}
                                        error={form.errors.nin}
                                        autoComplete="off"
                                    />
                                    <Select
                                        label="KYC tier"
                                        value={data.kyc_tier}
                                        onValueChange={(value) => form.setData('kyc_tier', value as 'tier1' | 'tier2' | 'tier3')}
                                        options={TIER_OPTIONS}
                                        description={form.errors.kyc_tier}
                                    />
                                    <Select
                                        label="Means of ID"
                                        value={data.means_of_id_type}
                                        onValueChange={(value) => form.setData('means_of_id_type', value)}
                                        options={ID_OPTIONS}
                                        description={form.errors.means_of_id_type}
                                    />
                                    <Input
                                        label="ID number"
                                        value={data.means_of_id_number}
                                        onChange={(event) => form.setData('means_of_id_number', event.target.value)}
                                        error={form.errors.means_of_id_number}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="ID expiry date"
                                        type="date"
                                        min={idExpiryFloor}
                                        value={data.id_expiry_date}
                                        onChange={(event) => form.setData('id_expiry_date', event.target.value)}
                                        error={form.errors.id_expiry_date}
                                    />
                                    <Select
                                        label="Proof of address"
                                        value={data.proof_of_address_type}
                                        onValueChange={(value) => form.setData('proof_of_address_type', value)}
                                        options={POA_OPTIONS}
                                        description={form.errors.proof_of_address_type}
                                    />
                                </div>
                            </div>
                        </div>
                    ) : null}

                    {step === 4 ? (
                        <div className={styles.wizardStep}>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Work and next of kin</h3>
                                <div className={styles.formGrid}>
                                    <Select
                                        label="Employment status"
                                        value={data.employment_status}
                                        onValueChange={(value) => form.setData('employment_status', value)}
                                        options={EMPLOYMENT_OPTIONS}
                                        description={form.errors.employment_status}
                                    />
                                    <Input
                                        label="Employer or business"
                                        value={data.employer_name}
                                        onChange={(event) => form.setData('employer_name', event.target.value)}
                                        error={form.errors.employer_name}
                                        autoComplete="off"
                                    />
                                    <div className={styles.spanFull}>
                                        <MoneyInput
                                            label="Monthly income"
                                            value={data.monthly_income}
                                            onValueChange={(value) => form.setData('monthly_income', value)}
                                            currencies={['NGN']}
                                            currency="NGN"
                                            locale="en-NG"
                                            quickAdd={[]}
                                            error={form.errors.monthly_income}
                                            description="Amount in naira."
                                        />
                                    </div>
                                    <Input
                                        label="Next of kin name"
                                        value={data.next_of_kin_name}
                                        onChange={(event) => form.setData('next_of_kin_name', event.target.value)}
                                        error={form.errors.next_of_kin_name}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Next of kin relationship"
                                        value={data.next_of_kin_relationship}
                                        onChange={(event) => form.setData('next_of_kin_relationship', event.target.value)}
                                        error={form.errors.next_of_kin_relationship}
                                        autoComplete="off"
                                    />
                                    <Input
                                        label="Next of kin phone"
                                        value={data.next_of_kin_phone}
                                        onChange={(event) => form.setData('next_of_kin_phone', event.target.value)}
                                        error={form.errors.next_of_kin_phone}
                                        autoComplete="off"
                                    />
                                </div>
                                <Textarea
                                    label="Notes"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(event) => form.setData('notes', event.target.value)}
                                    error={form.errors.notes}
                                />
                            </div>
                            <div className={styles.formSection}>
                                <h3 className={styles.formSectionTitle}>Review</h3>
                                <dl className={styles.reviewGrid}>
                                    <div className={styles.reviewRow}>
                                        <dt>Name</dt>
                                        <dd>{displayName || 'None'}</dd>
                                    </div>
                                    <div className={styles.reviewRow}>
                                        <dt>Phone</dt>
                                        <dd>{data.phone || 'None'}</dd>
                                    </div>
                                    <div className={styles.reviewRow}>
                                        <dt>Address</dt>
                                        <dd>{data.address || 'None'}</dd>
                                    </div>
                                    <div className={styles.reviewRow}>
                                        <dt>KYC</dt>
                                        <dd>{`${data.kyc_tier} BVN ${data.bvn || 'none'} NIN ${data.nin || 'none'}`}</dd>
                                    </div>
                                    <div className={styles.reviewRow}>
                                        <dt>Work</dt>
                                        <dd>{data.employment_status ? `${data.employment_status}${data.employer_name ? ` at ${data.employer_name}` : ''}` : 'None'}</dd>
                                    </div>
                                </dl>
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
                                    <Button type="button" variant="secondary" onClick={() => form.reset()}>
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
                                {mode === 'create' ? 'Create customer' : 'Save changes'}
                            </Button>
                        )}
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
