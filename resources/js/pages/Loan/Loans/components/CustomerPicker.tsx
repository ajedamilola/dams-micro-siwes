import { useEffect, useRef, useState } from 'react';
import { Combobox } from '@/components/arc/combobox/combobox';
import { Loader2 } from 'lucide-react';
import type { CustomerSearchHit } from '@/types/loan';
import styles from './loans.module.css';

const DEBOUNCE_MS = 350;
const THROTTLE_MS = 800;

async function fetchCustomers(query: string): Promise<CustomerSearchHit[]> {
    const params = new URLSearchParams({ q: query });
    const response = await fetch(`/loans/customers/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
        return [];
    }
    const json = await response.json();
    return Array.isArray(json.data) ? json.data : [];
}

interface CustomerPickerProps {
    value: string;
    onValueChange: (value: string) => void;
    initialOption?: Pick<CustomerSearchHit, 'id' | 'customer_no' | 'display_name'> | null;
    label?: string;
    description?: string;
    error?: string;
}

export function CustomerPicker({
    value,
    onValueChange,
    initialOption,
    label = 'Customer',
    description = 'Only KYC-verified customers can book loans.',
    error,
}: CustomerPickerProps) {
    const [options, setOptions] = useState<Pick<CustomerSearchHit, 'id' | 'customer_no' | 'display_name'>[]>(() =>
        initialOption ? [initialOption] : [],
    );
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const lastFetchAt = useRef(0);
    const timer = useRef<number | null>(null);
    const requestId = useRef(0);

    useEffect(
        () => () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    async function load(query: string): Promise<void> {
        const id = ++requestId.current;
        lastFetchAt.current = Date.now();
        setLoading(true);
        setFailed(false);
        try {
            const rows = await fetchCustomers(query);
            if (requestId.current !== id) {
                return;
            }
            setOptions((current) => {
                const seen = new Set(rows.map((row) => row.id));
                const kept = initialOption && !seen.has(initialOption.id) ? [initialOption] : [];
                void current;
                return [...rows, ...kept];
            });
        } catch {
            if (requestId.current === id) {
                setFailed(true);
            }
        } finally {
            if (requestId.current === id) {
                setLoading(false);
            }
        }
    }

    function schedule(query: string): void {
        if (timer.current !== null) {
            window.clearTimeout(timer.current);
            timer.current = null;
        }
        const run = () => {
            timer.current = null;
            void load(query);
        };
        if (Date.now() - lastFetchAt.current >= THROTTLE_MS) {
            run();
            return;
        }
        timer.current = window.setTimeout(
            run,
            Math.max(DEBOUNCE_MS, THROTTLE_MS - (Date.now() - lastFetchAt.current)),
        );
    }

    return (
        <div className={styles.pickerWrap}>
            {loading ? (
                <span className={styles.pickerSpinner} role="status" aria-label="Searching customers">
                    <Loader2 size={13} className="animate-spin" aria-hidden="true" />
                </span>
            ) : null}
            <Combobox
                label={label}
                value={value}
                onValueChange={onValueChange}
                onInput={(event) => schedule(event.currentTarget.value)}
                onFocus={() => {
                    if (options.length === 0) {
                        void load('');
                    }
                }}
                options={options.map((customer) => ({
                    value: customer.id,
                    label: `${customer.customer_no} ${customer.display_name}`,
                    keywords: [customer.customer_no, customer.display_name],
                }))}
                placeholder="Search by name, number or phone…"
                emptyMessage={
                    loading ? 'Searching customers…' : failed ? 'Search failed. Try again.' : 'No customers match this search'
                }
                description={error ?? description}
            />
        </div>
    );
}
