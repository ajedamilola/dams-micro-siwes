import { useState } from 'react';
import type { ReactNode } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { AppLayout } from '@/components/layout/AppLayout';
import { formatNGN } from '@/lib/format';
import { customerUrl, humanizeToken } from '@/lib/loan-customers';
import type { LoanCustomer } from '@/types/loan-customer';
import type { CompanyOption } from '@/types/ledger';
import { CustomerFormModal } from './components/CustomerFormModal';
import styles from './components/show.module.css';
import formStyles from './components/customers.module.css';

interface JournalLine {
    id: string;
    type: string;
    value: string;
    balance_after: string;
    ref: string | null;
    description: string | null;
    effective_date: string | null;
}

interface LinkableAccount {
    id: string;
    code: string;
    name: string;
}

interface ShowProps {
    customer: LoanCustomer;
    recentEntries: JournalLine[];
    linkableAccounts: LinkableAccount[];
}

type EntryRow = JournalLine & { actions: string } & Record<string, unknown>;

export default function CustomerShow({ customer, recentEntries }: ShowProps) {
    const [editing, setEditing] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const balance = Number(customer.account_balance);

    const entryRows: EntryRow[] = recentEntries.map((entry) => ({ ...entry, actions: entry.id }));

    const columns: DataColumn<EntryRow>[] = [
        { key: 'ref', label: 'Reference', width: 140 },
        { key: 'description', label: 'Description', sortable: false },
        { key: 'type', label: 'Side', width: 90 },
        {
            key: 'value',
            label: 'Amount',
            width: 160,
            render: (value) => <span>{formatNGN(Number(value))}</span>,
        },
        {
            key: 'balance_after',
            label: 'Balance after',
            width: 160,
            render: (value) => <span>{formatNGN(Number(value))}</span>,
        },
    ];

    function deleteCustomer(): void {
        router.delete(customerUrl(customer.id), {
            onSuccess: () => setConfirmingDelete(false),
            onError: (errors) =>
                setDeleteError(Object.values(errors)[0] ?? 'This customer could not be deleted.'),
        });
    }

    function verifyCustomer(): void {
        router.post(`/loans/customers/${customer.id}/verify`);
    }

    function sendToReview(status: 'in_review' | 'rejected' | 'pending'): void {
        router.post(`/loans/customers/${customer.id}/review`, { kyc_status: status });
    }

    const typeTone = customer.customer_type === 'individual' ? 'info' : customer.customer_type === 'group' ? 'warning' : 'neutral';
    const tierTone = customer.kyc_tier === 'tier3' ? 'success' : customer.kyc_tier === 'tier2' ? 'info' : 'neutral';
    const kycTone = customer.kyc_status === 'verified' ? 'success' : customer.kyc_status === 'rejected' ? 'danger' : customer.kyc_status === 'in_review' ? 'info' : 'neutral';
    const statusTone = customer.status === 'active' ? 'success' : customer.status === 'blacklisted' ? 'danger' : 'neutral';

    const personalFacts: { label: string; value: ReactNode }[] = [
        { label: 'Customer number', value: <span className={styles.code}>{customer.customer_no}</span> },
        {
            label: 'Type',
            value: (
                <Badge tone={typeTone} size="sm">
                    {humanizeToken(customer.customer_type)}
                </Badge>
            ),
        },
        { label: 'Gender', value: customer.gender ? humanizeToken(customer.gender) : 'None' },
        { label: 'Date of birth', value: customer.date_of_birth ?? 'None' },
        { label: 'Marital status', value: customer.marital_status ? humanizeToken(customer.marital_status) : 'None' },
        { label: 'Nationality', value: customer.nationality ?? 'None' },
        { label: 'Status', value: humanizeToken(customer.status) },
        ...(customer.business_name
            ? [{ label: 'Business', value: customer.business_name } as { label: string; value: ReactNode }]
            : []),
        ...(customer.rc_number
            ? [{ label: 'RC number', value: <span className={styles.code}>{customer.rc_number}</span> } as {
                  label: string;
                  value: ReactNode;
              }]
            : []),
    ];

    const contactFacts: { label: string; value: ReactNode }[] = [
        { label: 'Phone', value: <span className={styles.code}>{customer.phone}</span> },
        { label: 'Alt phone', value: customer.phone_alt ?? 'None' },
        { label: 'Email', value: customer.email ?? 'None' },
        { label: 'Address', value: customer.address },
        {
            label: 'City and state',
            value: `${customer.city ?? 'None'}${customer.state ? `, ${customer.state}` : ''}`,
        },
        { label: 'LGA', value: customer.lga ?? 'None' },
    ];

    const workFacts: { label: string; value: ReactNode }[] = [
        {
            label: 'Employment',
            value: customer.employment_status ? humanizeToken(customer.employment_status) : 'None',
        },
        { label: 'Employer', value: customer.employer_name ?? 'None' },
        {
            label: 'Monthly income',
            value: customer.monthly_income !== null ? formatNGN(Number(customer.monthly_income)) : 'None',
        },
        { label: 'Next of kin', value: customer.next_of_kin_name ?? 'None' },
        { label: 'Relationship', value: customer.next_of_kin_relationship ?? 'None' },
        { label: 'Kin phone', value: customer.next_of_kin_phone ?? 'None' },
    ];

    const kycFacts: { label: string; value: ReactNode }[] = [
        {
            label: 'KYC tier',
            value: (
                <Badge tone={tierTone} size="sm">
                    {humanizeToken(customer.kyc_tier)}
                </Badge>
            ),
        },
        {
            label: 'KYC status',
            value: (
                <Badge tone={kycTone} size="sm">
                    {customer.kyc_status.replace('_', ' ')}
                </Badge>
            ),
        },
        { label: 'BVN', value: customer.bvn_masked ?? 'None' },
        { label: 'NIN', value: customer.nin_masked ?? 'None' },
        {
            label: 'Means of ID',
            value: customer.means_of_id_type
                ? `${humanizeToken(customer.means_of_id_type)} ${customer.means_of_id_number ?? ''}`.trim()
                : 'None',
        },
        { label: 'ID expiry', value: customer.id_expiry_date ?? 'None' },
        { label: 'Proof of address', value: customer.proof_of_address_type ? humanizeToken(customer.proof_of_address_type) : 'None' },
    ];

    const accountFacts: { label: string; value: ReactNode }[] = [
        { label: 'Account', value: <span className={styles.code}>{customer.account_code ?? 'None'}</span> },
        { label: 'Balance', value: formatNGN(balance) },
        {
            label: 'Risk rating',
            value: (
                <Badge tone={customer.risk_rating === 'high' ? 'danger' : customer.risk_rating === 'medium' ? 'warning' : 'success'} size="sm">
                    {humanizeToken(customer.risk_rating)}
                </Badge>
            ),
        },
        { label: 'Politically exposed', value: customer.is_pep ? 'Yes' : 'No' },
    ];

    return (
        <>
            <Head title={`${customer.customer_no} ${customer.display_name}`} />
            <main className={styles.page}>
                <nav className={styles.crumbs} aria-label="Breadcrumb">
                    <ol>
                        <li>
                            <Link href="/loans/customers" className={styles.crumbLink}>
                                Loan customers
                            </Link>
                        </li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">{customer.display_name}</li>
                    </ol>
                </nav>

                <div className={styles.header}>
                    <div>
                        <h1 className={styles.title}>{customer.display_name}</h1>
                        <div className={styles.badges}>
                            <Badge tone="neutral" size="sm">
                                {customer.customer_no}
                            </Badge>
                            <Badge tone={typeTone} size="sm">
                                {humanizeToken(customer.customer_type)}
                            </Badge>
                            <Badge tone={tierTone} size="sm">
                                {humanizeToken(customer.kyc_tier)}
                            </Badge>
                            <Badge tone={kycTone} size="sm">
                                {customer.kyc_status.replace('_', ' ')}
                            </Badge>
                            <Badge tone={statusTone} size="sm">
                                {customer.status}
                            </Badge>
                        </div>
                    </div>
                    <div className={styles.actions}>
                        {customer.kyc_status !== 'verified' ? (
                            <Button variant="secondary" onClick={verifyCustomer}>
                                Verify KYC
                            </Button>
                        ) : null}
                        {customer.kyc_status === 'pending' ? (
                            <Button variant="secondary" onClick={() => sendToReview('in_review')}>
                                Start review
                            </Button>
                        ) : null}
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setDeleteError(null);
                                setConfirmingDelete(true);
                            }}
                        >
                            Delete
                        </Button>
                        <Button onClick={() => setEditing(true)}>Edit</Button>
                    </div>
                </div>

                <div className={styles.stats}>
                    <section className={styles.statCard} aria-labelledby="balance-heading">
                        <h2 id="balance-heading" className={styles.statLabel}>
                            Account balance
                        </h2>
                        <p className={`${styles.statValue} ${balance < 0 ? styles.negative : ''}`}>{formatNGN(balance)}</p>
                        <p className={styles.statNote}>
                            {customer.account_code
                                ? `Posted in ${customer.account_code}. Balances move through entries.`
                                : 'No account linked yet.'}
                        </p>
                    </section>
                    <section className={styles.statCard} aria-labelledby="contact-heading">
                        <h2 id="contact-heading" className={styles.statLabel}>
                            Contact
                        </h2>
                        <p className={styles.statValue}>{customer.phone}</p>
                        <p className={styles.statNote}>
                            {[customer.address, customer.city, customer.state].filter(Boolean).join(', ') || 'No address on file.'}
                        </p>
                    </section>
                    <section className={styles.statCard} aria-labelledby="kyc-position-heading">
                        <h2 id="kyc-position-heading" className={styles.statLabel}>
                            KYC position
                        </h2>
                        <div className={styles.statBadges}>
                            <Badge tone={tierTone} size="sm">
                                {humanizeToken(customer.kyc_tier)}
                            </Badge>
                            <Badge tone={kycTone} size="sm">
                                {customer.kyc_status.replace('_', ' ')}
                            </Badge>
                        </div>
                        <p className={styles.statNote}>
                            {customer.bvn_masked && customer.nin_masked
                                ? `BVN ${customer.bvn_masked} NIN ${customer.nin_masked}`
                                : 'Identifiers incomplete.'}
                        </p>
                    </section>
                </div>

                <div className={styles.content}>
                    <div className={styles.mainCol}>
                        <section aria-labelledby="personal-heading">
                            <h2 id="personal-heading" className={styles.sectionTitle}>
                                Personal details
                            </h2>
                            <dl className={styles.facts}>
                                {personalFacts.map((fact) => (
                                    <div key={fact.label} className={styles.fact}>
                                        <dt>{fact.label}</dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </section>

                        <section aria-labelledby="contact-details-heading">
                            <h2 id="contact-details-heading" className={styles.sectionTitle}>
                                Contact and address
                            </h2>
                            <dl className={styles.facts}>
                                {contactFacts.map((fact) => (
                                    <div key={fact.label} className={styles.fact}>
                                        <dt>{fact.label}</dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </section>

                        <section aria-labelledby="work-heading">
                            <h2 id="work-heading" className={styles.sectionTitle}>
                                Employment and next of kin
                            </h2>
                            <dl className={styles.facts}>
                                {workFacts.map((fact) => (
                                    <div key={fact.label} className={styles.fact}>
                                        <dt>{fact.label}</dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                            {customer.notes ? <p className={styles.description}>{customer.notes}</p> : null}
                        </section>

                        <section aria-labelledby="entries-heading">
                            <h2 id="entries-heading" className={styles.sectionTitle}>
                                Recent postings
                            </h2>
                            {entryRows.length > 0 ? (
                                <SortableDataTable
                                    rows={entryRows}
                                    columns={columns}
                                    rowKey="id"
                                    caption={`Recent postings for ${customer.display_name}`}
                                    emptyMessage="No postings to show"
                                    defaultSort={{ key: 'ref', direction: 'asc' }}
                                    itemName={{ one: 'entry', other: 'entries' }}
                                />
                            ) : (
                                <p className={styles.heroNote}>No posted entries on the linked account yet.</p>
                            )}
                        </section>
                    </div>

                    <div className={styles.sideCol}>
                        <section aria-labelledby="kyc-heading">
                            <h2 id="kyc-heading" className={styles.sectionTitle}>
                                KYC summary
                            </h2>
                            <dl className={styles.facts}>
                                {kycFacts.map((fact) => (
                                    <div key={fact.label} className={styles.fact}>
                                        <dt>{fact.label}</dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                            <p className={styles.kycNote}>
                                Nigerian three tier KYC applies. Tier 1 needs BVN or NIN with a 50,000 single deposit and 300,000
                                cumulative limit. Tier 2 needs BVN and NIN with a 100,000 single deposit and 500,000 cumulative
                                limit. Tier 3 is full KYC with BVN, NIN, a valid ID and proof of address. Loan customers need Tier 3
                                before verification.
                            </p>
                        </section>

                        <section aria-labelledby="account-heading">
                            <h2 id="account-heading" className={styles.sectionTitle}>
                                Account and risk
                            </h2>
                            <dl className={styles.facts}>
                                {accountFacts.map((fact) => (
                                    <div key={fact.label} className={styles.fact}>
                                        <dt>{fact.label}</dt>
                                        <dd>{fact.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    </div>
                </div>

                {editing ? (
                    <CustomerFormModal
                        key={`edit-${customer.id}`}
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setEditing(false);
                            }
                        }}
                        mode="edit"
                        initial={customer}
                        companies={[{ id: customer.company_id, name: customer.company_name ?? '', code: '' } as CompanyOption]}
                    />
                ) : null}

                {confirmingDelete ? (
                    <Dialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setConfirmingDelete(false);
                            }
                        }}
                    >
                        <DialogContent
                            title={`Delete ${customer.display_name}?`}
                            description="This removes the customer for good. Customers with postings cannot be deleted."
                        >
                            {deleteError ? (
                                <p className={formStyles.formError} role="alert">
                                    {deleteError}
                                </p>
                            ) : null}
                            <div className={formStyles.formActions}>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="button" variant="danger" onClick={deleteCustomer}>
                                    Delete customer
                                </Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                ) : null}
            </main>
        </>
    );
}

(CustomerShow as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Customer details" section="Loan customers">
        {page}
    </AppLayout>
);
