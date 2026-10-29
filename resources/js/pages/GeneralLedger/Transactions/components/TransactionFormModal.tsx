import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import { Textarea } from '@/components/arc/textarea/textarea';
import { toDateInput } from '@/lib/transactions';
import styles from './transactions.module.css';

interface TransactionFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'create' | 'edit';
    /** Id of the transaction being edited. Unused when creating. */
    transactionId?: string;
    /** Human reference shown in the title when editing. */
    transactionRef?: string;
    initial?: { description: string; effective_date: string } | null;
}

export function TransactionFormModal({
    open,
    onOpenChange,
    mode,
    transactionId,
    transactionRef,
    initial,
}: TransactionFormModalProps) {
    const form = useForm({
        description: initial?.description ?? '',
        effective_date: initial ? toDateInput(initial.effective_date) : '',
    });

    function handleSubmit(event: FormEvent): void {
        event.preventDefault();
        if (mode === 'create') {
            form.post('/general-ledger/transactions', {
                onSuccess: () => onOpenChange(false),
            });
        } else if (transactionId) {
            form.put(`/general-ledger/transactions/${transactionId}`, {
                onSuccess: () => onOpenChange(false),
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={
                    mode === 'create'
                        ? 'New transaction'
                        : `Edit ${transactionRef ?? ''}`
                }
                description={
                    mode === 'create'
                        ? 'Starts as a draft. Add debits and credits on its page, then submit it.'
                        : 'Only draft transactions can be edited.'
                }
            >
                <form
                    className={styles.form}
                    onSubmit={handleSubmit}
                    noValidate
                >
                    <Textarea
                        label="Description"
                        rows={3}
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                        error={form.errors.description}
                        description="What this transaction records."
                    />
                    <Input
                        label="Effective date"
                        type="date"
                        value={form.data.effective_date}
                        onChange={(event) =>
                            form.setData('effective_date', event.target.value)
                        }
                        error={form.errors.effective_date}
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
                            {mode === 'create'
                                ? 'Create transaction'
                                : 'Save changes'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
