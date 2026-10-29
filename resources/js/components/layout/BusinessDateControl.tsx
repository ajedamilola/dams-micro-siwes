import { useState } from 'react';
import type { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import { Button } from '@/components/arc/button/button';
import { Dialog, DialogClose, DialogContent } from '@/components/arc/dialog/dialog';
import { Input } from '@/components/arc/input/input';
import {
    addDays,
    formatBusinessDate,
    useBusinessDate,
    useUnpostedJournals,
} from '@/lib/business-date';
import styles from './business-date.module.css';

export function BusinessDateControl() {
    const businessDate = useBusinessDate();
    const unposted = useUnpostedJournals();
    const [open, setOpen] = useState(false);
    const nextDay = addDays(businessDate, 1);

    const form = useForm({ date: nextDay });

    function openDialog(): void {
        form.setData('date', nextDay);
        form.clearErrors();
        setOpen(true);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();
        form.post('/general-ledger/business-date', {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    if (!businessDate) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                className={styles.trigger}
                onClick={openDialog}
                aria-label={`Business date ${formatBusinessDate(businessDate)}. Open to advance.`}
            >
                <CalendarDays size={15} strokeWidth={1.75} aria-hidden="true" />
                <span className={styles.triggerDate}>{formatBusinessDate(businessDate)}</span>
                {unposted > 0 ? <span className={styles.triggerDot} aria-hidden="true" /> : null}
            </button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent
                    title="Business date"
                    description="All journals and loan activity are dated on or before this date. Move it forward only when the books are clean."
                >
                    <form className={styles.form} onSubmit={submit} noValidate>
                        <div className={styles.current}>
                            <span className={styles.currentLabel}>Current business date</span>
                            <span className={styles.currentValue}>{formatBusinessDate(businessDate)}</span>
                        </div>

                        <Input
                            label="New business date"
                            type="date"
                            min={nextDay}
                            value={form.data.date}
                            onChange={(event) => form.setData('date', event.target.value)}
                            error={form.errors.date}
                        />

                        {unposted > 0 ? (
                            <p className={styles.warning} role="alert">
                                {unposted} unposted {unposted === 1 ? 'journal' : 'journals'} must be posted or
                                cancelled before the date can move.
                            </p>
                        ) : (
                            <p className={styles.note}>
                                Every journal is posted. The date can move forward safely.
                            </p>
                        )}

                        <div className={styles.actions}>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                loading={form.processing}
                                disabled={form.processing || unposted > 0 || !form.data.date}
                            >
                                Advance date
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
