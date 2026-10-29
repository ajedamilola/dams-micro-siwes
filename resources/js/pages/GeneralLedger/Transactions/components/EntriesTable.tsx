import { useMemo } from 'react';
import { DropdownMenu } from '@/components/arc/dropdown-menu/dropdown-menu';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import { Ellipsis, Pencil, Trash } from 'lucide-react';
import { formatNGN } from '@/lib/format';
import type { JournalEntryRow } from '@/types/ledger';
import styles from './transactions.module.css';

/** One entry with its amount placed on the debit or credit side. */
export type EntryRow = JournalEntryRow & {
    debit_amount: number | null;
    credit_amount: number | null;
};

interface EntriesTableProps {
    entries: JournalEntryRow[];
    editable: boolean;
    onEdit: (entry: JournalEntryRow) => void;
    onDelete: (entry: JournalEntryRow) => void;
}

export function EntriesTable({
    entries,
    editable,
    onEdit,
    onDelete,
}: EntriesTableProps) {
    const rows = useMemo<EntryRow[]>(
        () =>
            entries.map((entry) => ({
                ...entry,
                debit_amount:
                    entry.type === 'debit' ? Number(entry.value) : null,
                credit_amount:
                    entry.type === 'credit' ? Number(entry.value) : null,
            })),
        [entries],
    );

    const columns = useMemo<DataColumn<EntryRow>[]>(
        () => [
            {
                key: 'id',
                label: '#',
                width: 64,
                render: (value) => (
                    <span className={styles.code}>{String(value)}</span>
                ),
            },
            {
                key: 'account_name',
                label: 'Account',
                sortable: false,
                render: (_value, row) => (
                    <span className={styles.accountCell}>
                        <span className={styles.accountCode}>
                            {row.account_code}
                        </span>
                        <span className={styles.nameLink}>
                            {row.account_name}
                        </span>
                    </span>
                ),
            },
            {
                key: 'debit_amount',
                label: 'Debit',
                width: 150,
                render: (value) =>
                    typeof value === 'number' ? (
                        formatNGN(value)
                    ) : (
                        <span className={styles.dash} aria-hidden="true">
                            –
                        </span>
                    ),
            },
            {
                key: 'credit_amount',
                label: 'Credit',
                width: 150,
                render: (value) =>
                    typeof value === 'number' ? (
                        formatNGN(value)
                    ) : (
                        <span className={styles.dash} aria-hidden="true">
                            –
                        </span>
                    ),
            },
            ...(editable
                ? [
                      {
                          key: 'journal_transaction_id',
                          label: 'Actions',
                          sortable: false,
                          render: (_value: unknown, row: EntryRow) => (
                              <DropdownMenu
                                  label={`Actions for entry ${row.id}`}
                                  icon={
                                      <Ellipsis size={15} aria-hidden="true" />
                                  }
                                  items={[
                                      {
                                          label: 'Edit',
                                          icon: (
                                              <Pencil
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          onSelect: () => onEdit(row),
                                      },
                                      {
                                          label: 'Remove',
                                          icon: (
                                              <Trash
                                                  size={15}
                                                  aria-hidden="true"
                                              />
                                          ),
                                          destructive: true,
                                          separatorBefore: true,
                                          onSelect: () => onDelete(row),
                                      },
                                  ]}
                              />
                          ),
                      } as DataColumn<EntryRow>,
                  ]
                : []),
        ],
        [editable, onEdit, onDelete],
    );

    return (
        <SortableDataTable
            rows={rows}
            columns={columns}
            rowKey="id"
            caption="Journal entries"
            emptyMessage="No journal entries to show"
            defaultSort={{ key: 'id', direction: 'asc' }}
            itemName={{ one: 'entry', other: 'entries' }}
        />
    );
}
