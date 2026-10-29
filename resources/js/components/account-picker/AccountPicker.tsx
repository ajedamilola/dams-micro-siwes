import { useEffect, useRef, useState } from 'react';
import { Combobox } from '@/components/arc/combobox/combobox';
import { Loader2 } from 'lucide-react';
import type { AccountOption } from '@/types/ledger';
import styles from './account-picker.module.css';

/** Pause after the last keystroke before a trailing request fires. */
const DEBOUNCE_MS = 350;
/** Minimum gap between requests so fast typing cannot flood the backend. */
const THROTTLE_MS = 800;

async function fetchAccounts(query: string): Promise<AccountOption[]> {
    const params = new URLSearchParams({ q: query });
    const response = await fetch(
        `/general-ledger/accounts/search?${params.toString()}`,
        { headers: { Accept: 'application/json' } },
    );
    if (!response.ok) {
        return [];
    }
    const json = await response.json();
    return Array.isArray(json.data) ? json.data : [];
}

interface AccountPickerProps {
    value: string;
    onValueChange: (value: string) => void;
    /** The selected account when editing, so its label resolves. */
    initialOption?: Pick<
        AccountOption,
        'id' | 'code' | 'name'
    > | null;
    label?: string;
    description?: string;
    error?: string;
}

/**
 * Searchable account picker backed by the server (10 matches per keyword).
 * Typing schedules a request that is debounced on pause and throttled to
 * one request per window; stale responses are ignored.
 */
export function AccountPicker({
    value,
    onValueChange,
    initialOption,
    label = 'Account',
    description = 'Only active leaf accounts can take entries.',
    error,
}: AccountPickerProps) {
    const [options, setOptions] = useState<
        Pick<AccountOption, 'id' | 'code' | 'name'>[]
    >(() => (initialOption ? [initialOption] : []));
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
            const rows = await fetchAccounts(query);
            if (requestId.current !== id) {
                return;
            }
            setOptions((current) => {
                const seen = new Set(rows.map((row) => row.id));
                const kept =
                    initialOption && !seen.has(initialOption.id)
                        ? [initialOption]
                        : [];
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
        // Throttle: a quiet window passed, so go on the leading edge.
        if (Date.now() - lastFetchAt.current >= THROTTLE_MS) {
            run();
            return;
        }
        // Otherwise wait for a typing pause, at least until the window ends.
        timer.current = window.setTimeout(
            run,
            Math.max(DEBOUNCE_MS, THROTTLE_MS - (Date.now() - lastFetchAt.current)),
        );
    }

    return (
        <div className={styles.pickerWrap}>
            {loading ? (
                <span
                    className={styles.pickerSpinner}
                    role="status"
                    aria-label="Searching accounts"
                >
                    <Loader2
                        size={13}
                        className="animate-spin"
                        aria-hidden="true"
                    />
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
                options={options.map((account) => ({
                    value: account.id,
                    label: `${account.code} ${account.name}`,
                    keywords: [account.code, account.name],
                }))}
                placeholder="Search by code or name…"
                emptyMessage={
                    loading
                        ? 'Searching accounts…'
                        : failed
                          ? 'Search failed. Try again.'
                          : 'No accounts match this search'
                }
                description={error ?? description}
            />
        </div>
    );
}
