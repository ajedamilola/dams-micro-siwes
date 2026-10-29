import { useState } from 'react';
import { Button } from '@/components/arc/button/button';
import { Input } from '@/components/arc/input/input';
import { AccountPicker } from '@/components/account-picker/AccountPicker';
import type { AccountRef, BalanceSheetHeading } from '@/types/ledger';
import { X } from 'lucide-react';
import styles from './settings.module.css';

interface HeadingEditorProps {
    heading: BalanceSheetHeading;
    accountsById: Record<string, AccountRef>;
    onChange: (heading: BalanceSheetHeading) => void;
    onRemove: () => void;
}

/**
 * One balance sheet heading: a renameable label with the slotted
 * accounts shown as removable chips plus a picker to slot more.
 */
export function HeadingEditor({
    heading,
    accountsById,
    onChange,
    onRemove,
}: HeadingEditorProps) {
    const [picked, setPicked] = useState('');
    const [pickerKey, setPickerKey] = useState(0);

    function addPicked(): void {
        if (picked === '' || heading.account_ids.includes(picked)) {
            return;
        }
        onChange({ ...heading, account_ids: [...heading.account_ids, picked] });
        setPicked('');
        setPickerKey((key) => key + 1);
    }

    return (
        <div className={styles.heading}>
            <div className={styles.headingHead}>
                <Input
                    label="Heading"
                    value={heading.label}
                    onChange={(event) =>
                        onChange({ ...heading, label: event.target.value })
                    }
                    autoComplete="off"
                />
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={onRemove}
                >
                    Remove
                </Button>
            </div>

            {heading.account_ids.length > 0 ? (
                <div className={styles.chips} aria-label="Slotted accounts">
                    {heading.account_ids.map((id) => {
                        const account = accountsById[id];
                        return (
                            <span className={styles.chip} key={id}>
                                <span>
                                    {account
                                        ? `${account.code} ${account.name}`
                                        : 'Unknown account'}
                                </span>
                                <button
                                    type="button"
                                    aria-label={`Remove ${account?.name ?? 'account'}`}
                                    onClick={() =>
                                        onChange({
                                            ...heading,
                                            account_ids:
                                                heading.account_ids.filter(
                                                    (entry) => entry !== id,
                                                ),
                                        })
                                    }
                                >
                                    <X size={13} aria-hidden="true" />
                                </button>
                            </span>
                        );
                    })}
                </div>
            ) : (
                <p className={styles.hint}>
                    No accounts slotted yet. This heading will show zero.
                </p>
            )}

            <div className={styles.addRow}>
                <AccountPicker
                    key={pickerKey}
                    label="Add account"
                    description="Search the chart of accounts."
                    value={picked}
                    onValueChange={setPicked}
                />
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    disabled={picked === ''}
                    onClick={addPicked}
                >
                    Add
                </Button>
            </div>
        </div>
    );
}
