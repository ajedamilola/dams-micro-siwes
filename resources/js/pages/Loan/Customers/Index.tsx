import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { AppLayout } from '@/components/layout/AppLayout';
import { customerUrl } from '@/lib/loan-customers';
import type { CompanyOption } from '@/types/ledger';
import type { KycTierOption, LoanCustomer } from '@/types/loan-customer';
import {
    CustomerToolbar,
    type CustomerStatusFilter,
    type CustomerTypeFilter,
    type KycStatusFilter,
    type KycTierFilter,
} from './components/CustomerToolbar';
import { CustomerFormModal } from './components/CustomerFormModal';
import { CustomersTable, type CustomerRow } from './components/CustomersTable';
import styles from './components/customers.module.css';

interface CustomersIndexProps {
    customers: LoanCustomer[];
    companies: CompanyOption[];
    kycTiers: KycTierOption[];
}

export default function CustomersIndex({ customers, companies }: CustomersIndexProps) {
    const [query, setQuery] = useState('');
    const [kycStatusFilter, setKycStatusFilter] = useState<KycStatusFilter>('all');
    const [kycTierFilter, setKycTierFilter] = useState<KycTierFilter>('all');
    const [statusFilter, setStatusFilter] = useState<CustomerStatusFilter>('all');
    const [typeFilter, setTypeFilter] = useState<CustomerTypeFilter>('all');
    const [modal, setModal] = useState<{ mode: 'create' } | { mode: 'edit'; id: string } | null>(null);
    const [deleting, setDeleting] = useState<CustomerRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | null>(null);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const rows = useMemo<CustomerRow[]>(
        () => customers.map((customer) => ({ ...customer, balance: Number(customer.account_balance) })),
        [customers],
    );

    const filteredRows = useMemo(() => {
        const needle = query.trim().toLowerCase();
        return rows.filter((row) => {
            if (kycStatusFilter !== 'all' && row.kyc_status !== kycStatusFilter) {
                return false;
            }
            if (kycTierFilter !== 'all' && row.kyc_tier !== kycTierFilter) {
                return false;
            }
            if (statusFilter !== 'all' && row.status !== statusFilter) {
                return false;
            }
            if (typeFilter !== 'all' && row.customer_type !== typeFilter) {
                return false;
            }
            if (needle === '') {
                return true;
            }
            return (
                row.display_name.toLowerCase().includes(needle) ||
                row.customer_no.toLowerCase().includes(needle) ||
                row.phone.toLowerCase().includes(needle) ||
                (row.business_name ?? '').toLowerCase().includes(needle)
            );
        });
    }, [rows, query, kycStatusFilter, kycTierFilter, statusFilter, typeFilter]);

    const filtersActive =
        query.trim() !== '' ||
        kycStatusFilter !== 'all' ||
        kycTierFilter !== 'all' ||
        statusFilter !== 'all' ||
        typeFilter !== 'all';

    function clearFilters(): void {
        setQuery('');
        setKycStatusFilter('all');
        setKycTierFilter('all');
        setStatusFilter('all');
        setTypeFilter('all');
    }

    function toggleStatus(row: CustomerRow): void {
        const next = row.status === 'active' ? 'inactive' : 'active';
        router.put(customerUrl(row.id), { status: next });
    }

    function confirmDelete(row: CustomerRow): void {
        setDeleteError(null);
        setDeleting(row);
    }

    function deleteCustomer(): void {
        if (!deleting) {
            return;
        }
        router.delete(customerUrl(deleting.id), {
            onSuccess: () => setDeleting(null),
            onError: (errors) => setDeleteError(Object.values(errors)[0] ?? 'This customer could not be deleted.'),
        });
    }

    return (
        <>
            <Head title="Loan customers" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>Loan customers</h1>
                        <p className={styles.description}>
                            Every loan customer with Nigerian KYC and a linked account in the Customers subledger.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button onClick={() => setModal({ mode: 'create' })}>New customer</Button>
                    </div>
                </div>

                <CustomerToolbar
                    query={query}
                    onQueryChange={setQuery}
                    kycStatusFilter={kycStatusFilter}
                    onKycStatusChange={setKycStatusFilter}
                    kycTierFilter={kycTierFilter}
                    onKycTierChange={setKycTierFilter}
                    statusFilter={statusFilter}
                    onStatusChange={setStatusFilter}
                    typeFilter={typeFilter}
                    onTypeChange={setTypeFilter}
                />

                <CustomersTable
                    rows={filteredRows}
                    totalFiltered={filteredRows.length}
                    totalAll={rows.length}
                    onView={(row) => router.visit(customerUrl(row.id))}
                    onEdit={(row) => setModal({ mode: 'edit', id: row.id })}
                    onDelete={confirmDelete}
                    onToggleStatus={(row) => toggleStatus(row)}
                    filtersActive={filtersActive}
                    onClearFilters={clearFilters}
                    loading={!mounted}
                />

                {modal ? (
                    <CustomerFormModal
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

                {deleting ? (
                    <Dialog
                        open
                        onOpenChange={(open) => {
                            if (!open) {
                                setDeleting(null);
                            }
                        }}
                    >
                        <DialogContent
                            title={`Delete ${deleting.display_name}?`}
                            description="This removes the customer for good. Customers with postings cannot be deleted."
                        >
                            {deleteError ? (
                                <p className={styles.formError} role="alert">
                                    {deleteError}
                                </p>
                            ) : null}
                            <div className={styles.formActions}>
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

(CustomersIndex as unknown as { layout: (page: ReactNode) => ReactNode }).layout = (page) => (
    <AppLayout title="Loan customers" section="Loan">
        {page}
    </AppLayout>
);
