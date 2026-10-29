import type { JournalEntryRow, TransactionStatus } from '@/types/ledger';

export function transactionUrl(id: string): string {
    return `/general-ledger/transactions/${id}`;
}

export function entryUrl(id: string, entryId?: number): string {
    return entryId === undefined
        ? `/general-ledger/transactions/${id}/entries`
        : `/general-ledger/transactions/${id}/entries/${entryId}`;
}

export const TRANSACTION_STATUS_TONE: Record<
    TransactionStatus,
    'neutral' | 'info' | 'success' | 'warning' | 'danger'
> = {
    DRAFT: 'neutral',
    SUBMITTED: 'info',
    POSTED: 'success',
    REVERSED: 'warning',
    CANCELLED: 'danger',
};

const dateFormat = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormat = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatDate(iso: string): string {
    return dateFormat.format(new Date(iso));
}

export function formatDateTime(iso: string): string {
    return dateTimeFormat.format(new Date(iso));
}

/** YYYY-MM-DD for native date inputs. */
export function toDateInput(iso: string): string {
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
}

/** Signed totals from entry values, in major units. */
export function entryTotals(entries: JournalEntryRow[]): {
    debits: number;
    credits: number;
} {
    let debits = 0;
    let credits = 0;
    for (const entry of entries) {
        const value = Number(entry.value);
        if (entry.type === 'debit') {
            debits += value;
        } else {
            credits += value;
        }
    }
    return { debits, credits };
}

export function isBalanced(entries: JournalEntryRow[]): boolean {
    if (entries.length === 0) {
        return false;
    }
    const { debits, credits } = entryTotals(entries);
    return Math.abs(debits - credits) < 0.005;
}
