import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/arc/button/button';
import { AppLayout } from '@/components/layout/AppLayout';
import type {
    AccountRef,
    BalanceSheetSection,
    CompanyOption,
} from '@/types/ledger';
import { HeadingEditor } from './components/HeadingEditor';
import styles from './components/settings.module.css';

interface SettingsIndexProps {
    company: CompanyOption;
    sections: BalanceSheetSection[];
    accounts: AccountRef[];
    unmapped_accounts: AccountRef[];
}

export default function SettingsIndex({
    company,
    sections: initialSections,
    accounts,
    unmapped_accounts,
}: SettingsIndexProps) {
    const [sections, setSections] =
        useState<BalanceSheetSection[]>(initialSections);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const accountsById = useMemo<Record<string, AccountRef>>(
        () => Object.fromEntries(accounts.map((row) => [row.id, row])),
        [accounts],
    );

    function updateHeading(
        sectionKey: string,
        index: number,
        heading: BalanceSheetSection['headings'][number],
    ): void {
        setSections((current) =>
            current.map((section) =>
                section.key === sectionKey
                    ? {
                          ...section,
                          headings: section.headings.map((entry, position) =>
                              position === index ? heading : entry,
                          ),
                      }
                    : section,
            ),
        );
    }

    function removeHeading(sectionKey: string, index: number): void {
        setSections((current) =>
            current.map((section) =>
                section.key === sectionKey
                    ? {
                          ...section,
                          headings: section.headings.filter(
                              (_, position) => position !== index,
                          ),
                      }
                    : section,
            ),
        );
    }

    function addHeading(sectionKey: string): void {
        setSections((current) =>
            current.map((section) =>
                section.key === sectionKey
                    ? {
                          ...section,
                          headings: [
                              ...section.headings,
                              {
                                  label: `New heading ${section.headings.length + 1}`,
                                  account_ids: [],
                              },
                          ],
                      }
                    : section,
            ),
        );
    }

    function save(): void {
        setError(null);
        setSaving(true);
        router.put(
            '/general-ledger/settings',
            { sections },
            {
                onSuccess: () => setSaving(false),
                onError: (errors) => {
                    setSaving(false);
                    setError(
                        Object.values(errors)[0] ??
                            'The layout could not be saved.',
                    );
                },
            },
        );
    }

    return (
        <>
            <Head title="Report settings" />
            <main className={styles.page}>
                <div className={styles.header}>
                    <div className={styles.headerText}>
                        <h1 className={styles.title}>Report settings</h1>
                        <p className={styles.description}>
                            {company.name} · Slot each balance sheet heading
                            with the accounts that make it up. Unmapped
                            balances still balance the sheet through the
                            current period surplus.
                        </p>
                    </div>
                    <div className={styles.actions}>
                        <Button
                            onClick={save}
                            loading={saving}
                            disabled={saving}
                        >
                            Save changes
                        </Button>
                    </div>
                </div>

                {error ? (
                    <p className={styles.formError} role="alert">
                        {error}
                    </p>
                ) : null}

                {sections.map((section) => (
                    <section
                        className={styles.card}
                        aria-labelledby={`section-${section.key}`}
                        key={section.key}
                    >
                        <div>
                            <h2
                                id={`section-${section.key}`}
                                className={styles.cardTitle}
                            >
                                {section.label}
                            </h2>
                            <p className={styles.count}>
                                {section.headings.length} headings
                            </p>
                        </div>

                        {section.headings.map((heading, index) => (
                            <HeadingEditor
                                key={`${section.key}-${index}`}
                                heading={heading}
                                accountsById={accountsById}
                                onChange={(next) =>
                                    updateHeading(section.key, index, next)
                                }
                                onRemove={() =>
                                    removeHeading(section.key, index)
                                }
                            />
                        ))}

                        <div>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={() => addHeading(section.key)}
                            >
                                Add heading
                            </Button>
                        </div>
                    </section>
                ))}

                <section
                    className={styles.card}
                    aria-labelledby="unmapped-heading"
                >
                    <div>
                        <h2
                            id="unmapped-heading"
                            className={styles.cardTitle}
                        >
                            Unmapped accounts
                        </h2>
                        <p className={styles.count}>
                            {unmapped_accounts.length} postable accounts outside
                            every heading
                        </p>
                    </div>
                    {unmapped_accounts.length > 0 ? (
                        <div className={styles.unmapped}>
                            {unmapped_accounts.map((row) => (
                                <span
                                    key={row.id}
                                    className={styles.unmappedName}
                                >
                                    {row.code} {row.name}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <p className={styles.hint}>
                            Every postable account is slotted into a heading.
                        </p>
                    )}
                </section>
            </main>
        </>
    );
}

(
    SettingsIndex as unknown as { layout: (page: ReactNode) => ReactNode }
).layout = (page) => (
    <AppLayout title="Report settings" section="General ledger">
        {page}
    </AppLayout>
);
