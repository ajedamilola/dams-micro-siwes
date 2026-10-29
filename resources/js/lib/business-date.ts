import { usePage } from '@inertiajs/react';

interface BusinessDateProps {
    business_date?: string;
    unposted_journals?: number;
}

/** The shared business date every date picker and calculation must respect. */
export function useBusinessDate(): string {
    const props = usePage().props as unknown as BusinessDateProps;

    return props.business_date ?? '';
}

export function useUnpostedJournals(): number {
    const props = usePage().props as unknown as BusinessDateProps;

    return props.unposted_journals ?? 0;
}

export function addDays(iso: string, days: number): string {
    const date = new Date(`${iso}T00:00:00`);
    date.setDate(date.getDate() + days);

    return toIso(date);
}

export function addMonths(iso: string, months: number): string {
    const date = new Date(`${iso}T00:00:00`);
    date.setMonth(date.getMonth() + months);

    return toIso(date);
}

export function toIso(date: Date): string {
    return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

export function formatBusinessDate(iso: string): string {
    if (!iso) {
        return '';
    }

    return new Date(`${iso}T00:00:00`).toLocaleDateString('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
