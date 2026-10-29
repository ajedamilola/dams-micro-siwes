import { Button } from '@/components/arc/button/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/arc/dialog/dialog';
import styles from './transactions.module.css';

interface ConfirmDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    confirmLabel: string;
    danger?: boolean;
    processing?: boolean;
    error?: string | null;
    onConfirm: () => void;
}

export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    danger,
    processing,
    error,
    onConfirm,
}: ConfirmDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent title={title} description={description}>
                {error ? (
                    <p className={styles.formError} role="alert">
                        {error}
                    </p>
                ) : null}
                <div className={styles.formActions}>
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Back
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant={danger ? 'danger' : 'primary'}
                        loading={processing}
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
