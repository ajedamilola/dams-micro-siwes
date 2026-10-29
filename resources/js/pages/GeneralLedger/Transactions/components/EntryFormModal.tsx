import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import { MoneyInput } from '@/components/arc/money-input/money-input';
import { Select } from '@/components/arc/select/select';
import { entryUrl } from '@/lib/transactions';
import type {
    AccountOption,
    EntryType,
    JournalEntryRow,
} from '@/types/ledger';
import { AccountPicker } from '@/components/account-picker/AccountPicker';
import styles from './transactions.module.css';

const TYPE_OPTIONS = [
    { value: 'debit', label: 'Debit' },
    { value: 'credit', label: 'Credit' },
];

interface EntryFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    transactionId: string;
    /** Entry being edited. Null when adding. */
    initial?: JournalEntryRow | null;
}

function toKobo(value: string): number | null {
    const amount = Number(value);
    return Number.isFinite(amount) ? Math.round(amount * 100) : null;
}

function toOption(entry: JournalEntryRow): AccountOption {
    return {
        id: entry.account_id,
        code: entry.account_code,
        name: entry.account_name,
        balance_type: entry.account_balance_type,
        status: 'active',
        ledger_id: entry.account_ledger_id,
    };
}

export function EntryFormModal({
    open,
    onOpenChange,
    transactionId,
    initial,
}: EntryFormModalProps) {
    const editing = initial !== null && initial !== undefined;

    const form = useForm({
        account_id: initial?.account_id ?? '',
        type: (initial?.type ?? 'debit') as EntryType,
        kobo: initial ? toKobo(initial.value) : (null as number | null),
    });

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        form.transform((data) => ({
            account_id: data.account_id,
            type: data.type,
            value:
                data.kobo === null
                    ? ''
                    : (data.kobo / 100).toFixed(2),
        }));
        if (editing && initial) {
            form.put(entryUrl(transactionId, initial.id), {
                onSuccess: () => onOpenChange(false),
            });
        } else {
            form.post(entryUrl(transactionId), {
                onSuccess: () => onOpenChange(false),
            });
        }
    }

    const errors = form.errors as unknown as Record<string, string | undefined>;
    const accountError =
        errors.account_id ?? errors.account ?? errors.entry;
    const amountError = errors.value ?? errors.kobo;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={editing ? 'Edit journal entry' : 'Add journal entry'}
                description="Debits and credits must balance before the transaction can be submitted."
            >
                <form
                    className={styles.form}
                    onSubmit={handleSubmit}
                    noValidate
                >
                    <AccountPicker
                        value={form.data.account_id}
                        onValueChange={(value) =>
                            form.setData('account_id', value)
                        }
                        initialOption={initial ? toOption(initial) : null}
                        error={accountError}
                    />
                    <Select
                        label="Type"
                        value={form.data.type}
                        onValueChange={(value) =>
                            form.setData('type', value as EntryType)
                        }
                        options={TYPE_OPTIONS}
                    />
                    <MoneyInput
                        label="Amount"
                        value={form.data.kobo}
                        onValueChange={(value) => form.setData('kobo', value)}
                        currencies={['NGN']}
                        currency="NGN"
                        locale="en-NG"
                        quickAdd={[]}
                        error={amountError}
                        description="Amount in naira."
                    />
                    <div className={styles.formActions}>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            loading={form.processing}
                            disabled={form.processing}
                        >
                            {editing ? 'Save entry' : 'Add entry'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
