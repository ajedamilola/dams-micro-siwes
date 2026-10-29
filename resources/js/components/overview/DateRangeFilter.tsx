import { useState } from 'react';
import type { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { Input } from '@/components/arc/input/input';
import type { OverviewFilters } from '@/types/overview';
import styles from './date-range-filter.module.css';

interface DateRangeFilterProps {
    filters: OverviewFilters;
    url: string;
}

function isoDate(date: Date): string {
    return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

function presetRange(days: number): OverviewFilters {
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - (days - 1));

    return { from: isoDate(from), to: isoDate(to) };
}

const PRESETS = [
    { label: '7d', days: 7 },
    { label: '30d', days: 30 },
    { label: '90d', days: 90 },
];

export function DateRangeFilter({ filters, url }: DateRangeFilterProps) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [error, setError] = useState<string | null>(null);

    function visit(next: OverviewFilters): void {
        if (!next.from || !next.to) {
            setError('Choose both a start and an end date.');
            return;
        }
        if (next.from > next.to) {
            setError('The start date must be on or before the end date.');
            return;
        }
        setError(null);
        router.get(url, { from: next.from, to: next.to }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function applyPreset(days: number): void {
        const next = presetRange(days);
        setFrom(next.from);
        setTo(next.to);
        visit(next);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();
        visit({ from, to });
    }

    return (
        <form className={styles.form} onSubmit={submit}>
            <div className={styles.presets} role="group" aria-label="Quick ranges">
                {PRESETS.map((preset) => (
                    <Button
                        key={preset.days}
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => applyPreset(preset.days)}
                    >
                        {preset.label}
                    </Button>
                ))}
            </div>
            <div className={styles.dates}>
                <Input
                    label="From"
                    type="date"
                    value={from}
                    max={to}
                    onChange={(event) => setFrom(event.target.value)}
                />
                <Input
                    label="To"
                    type="date"
                    value={to}
                    min={from}
                    onChange={(event) => setTo(event.target.value)}
                />
                <Button type="submit" size="sm" className={styles.apply}>
                    Apply
                </Button>
            </div>
            {error ? (
                <p className={styles.error} role="alert">
                    {error}
                </p>
            ) : null}
        </form>
    );
}
