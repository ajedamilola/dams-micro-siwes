import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { Select } from '@/components/arc/select/select';
import { Textarea } from '@/components/arc/textarea/textarea';
import { ACCOUNT_CATEGORIES, accountUrl, subtreeIds } from '@/lib/accounts';
import type {
    ChartAccount,
    NormalBalance,
    AccountStatus,
} from '@/types/account';
import type { LedgerOption } from '@/types/ledger';
import styles from './accounts.module.css';

export interface AccountRowRef {
    id: string;
    code: string;
    name: string;
    parent_id: string | null;
    is_group: boolean;
    ledger_id: string;
}

interface AccountFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'create' | 'edit';
    initial: ChartAccount | null;
    rows: AccountRowRef[];
    ledgers: LedgerOption[];
    defaultLedgerId: string;
}

const BALANCE_OPTIONS = [
    { value: 'debit', label: 'Debit' },
    { value: 'credit', label: 'Credit' },
];

const STATUS_OPTIONS = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

export function AccountFormModal({
    open,
    onOpenChange,
    mode,
    initial,
    rows,
    ledgers,
    defaultLedgerId,
}: AccountFormModalProps) {
    const form = useForm({
        ledger_id: initial?.ledger_id ?? defaultLedgerId,
        code: initial?.code ?? '',
        name: initial?.name ?? '',
        description: initial?.description ?? '',
        parent_id: initial?.parent_id ?? '',
        category: initial?.category ?? 'Uncategorized',
        balance_type: (initial?.balance_type ?? 'debit') as NormalBalance,
        status: (initial?.status ?? 'active') as AccountStatus,
    });

    const ledgerLocked = mode === 'edit';
    const hasParent = form.data.parent_id !== '';
    const excluded =
        mode === 'edit' && initial ? subtreeIds(rows, initial.id) : [];
    const parents = rows.filter(
        (row) =>
            row.is_group &&
            row.ledger_id === form.data.ledger_id &&
            !excluded.includes(row.id),
    );

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            code: data.code.trim(),
            name: data.name.trim(),
            description:
                data.description.trim() === '' ? null : data.description.trim(),
            parent_id: data.parent_id === '' ? null : data.parent_id,
        }));
        if (mode === 'create') {
            form.post('/general-ledger/accounts', {
                onSuccess: () => onOpenChange(false),
            });
        } else if (initial) {
            form.put(accountUrl(initial.id, initial.ledger_id), {
                onSuccess: () => onOpenChange(false),
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={
                    mode === 'create'
                        ? 'New account'
                        : `Edit ${initial?.name ?? 'account'}`
                }
                description={
                    mode === 'create'
                        ? 'Opens at a zero balance. Balances move through journal entries.'
                        : 'Category and normal balance follow the parent account.'
                }
            >
                <form
                    className={styles.form}
                    onSubmit={handleSubmit}
                    noValidate
                >
                    <Select
                        label="Ledger"
                        value={form.data.ledger_id}
                        onValueChange={(value) => {
                            form.setData('ledger_id', value);
                            form.setData('parent_id', '');
                        }}
                        options={ledgers.map((ledger) => ({
                            value: ledger.id,
                            label: `${ledger.code} ${ledger.name}`,
                        }))}
                        disabled={ledgerLocked}
                        description={
                            ledgerLocked
                                ? 'Accounts stay in their ledger.'
                                : 'Codes are unique within a ledger.'
                        }
                    />
                    <Input
                        label="Code"
                        value={form.data.code}
                        onChange={(event) =>
                            form.setData('code', event.target.value)
                        }
                        error={form.errors.code}
                        autoComplete="off"
                    />
                    <Input
                        label="Name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        error={form.errors.name}
                        autoComplete="off"
                    />
                    <Textarea
                        label="Description"
                        rows={3}
                        value={form.data.description ?? ''}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                        error={form.errors.description}
                    />
                    <Select
                        label="Parent account"
                        value={form.data.parent_id}
                        onValueChange={(value) =>
                            form.setData('parent_id', value)
                        }
                        options={[
                            {
                                value: '',
                                label: 'Top level (no parent)',
                            },
                            ...parents.map((parent) => ({
                                value: parent.id,
                                label: `${parent.code} ${parent.name}`,
                            })),
                        ]}
                        description={form.errors.parent_id}
                    />
                    <Select
                        label="Category"
                        value={form.data.category}
                        onValueChange={(value) =>
                            form.setData('category', value)
                        }
                        options={ACCOUNT_CATEGORIES}
                        disabled={hasParent}
                        description={
                            hasParent
                                ? 'Inherited from the parent account.'
                                : 'Drives the account type.'
                        }
                    />
                    <Select
                        label="Normal balance"
                        value={form.data.balance_type}
                        onValueChange={(value) =>
                            form.setData('balance_type', value as NormalBalance)
                        }
                        options={BALANCE_OPTIONS}
                        disabled={hasParent}
                        description={
                            hasParent
                                ? 'Inherited from the parent account.'
                                : undefined
                        }
                    />
                    <Select
                        label="Status"
                        value={form.data.status}
                        onValueChange={(value) =>
                            form.setData('status', value as AccountStatus)
                        }
                        options={STATUS_OPTIONS}
                    />
                    <div className={styles.formActions}>
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => form.reset()}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            loading={form.processing}
                            disabled={form.processing}
                        >
                            {mode === 'create'
                                ? 'Create account'
                                : 'Save changes'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
