import { useMemo } from 'react';
import { Badge } from '@/components/arc/badge/badge';
import { Button } from '@/components/arc/button/button';
import { DropdownMenu } from '@/components/arc/dropdown-menu/dropdown-menu';
import { EmptyState } from '@/components/arc/empty-state/empty-state';
import { Skeleton } from '@/components/arc/skeleton/skeleton';
import {
    SortableDataTable,
    type DataColumn,
} from '@/components/arc/sortable-data-table/sortable-data-table';
import {
    Ban,
    ChevronRight,
    CircleCheck,
    Ellipsis,
    Eye,
    Pencil,
    SearchX,
    Trash,
} from 'lucide-react';
import { capitalize, formatNGN } from '@/lib/format';
import type { ChartAccount } from '@/types/account';
import styles from './accounts.module.css';

/** A chart account prepared for the table: parsed balance plus menu key. */
export type AccountRow = ChartAccount & {
    balance: number;
    actions: string;
};

export const TYPE_TONE: Record<
    ChartAccount['type'],
    'info' | 'warning' | 'neutral' | 'success' | 'danger'
> = {
    Asset: 'info',
    Liability: 'warning',
    Equity: 'neutral',
    Revenue: 'success',
    Expense: 'danger',
};

interface AccountNameProps {
    row: AccountRow;
    collapsed: boolean;
    showLedger: boolean;
    onToggle: () => void;
}

function AccountName({
    row,
    collapsed,
    showLedger,
    onToggle,
}: AccountNameProps) {
    return (
        <span
            className={styles.nameCell}
            style={{ paddingLeft: row.level * 20 }}
        >
            {row.is_group ? (
                <button
                    type="button"
                    className={styles.chevron}
                    data-open={collapsed ? undefined : ''}
                    aria-expanded={!collapsed}
                    aria-label={`${collapsed ? 'Expand' : 'Collapse'} ${row.name}`}
                    onClick={onToggle}
                >
                    <ChevronRight
                        size={16}
                        strokeWidth={2}
                        aria-hidden="true"
                    />
                </button>
            ) : (
                <span className={styles.leafSpacer} aria-hidden="true" />
            )}
            <span className={row.is_group ? styles.groupName : undefined}>
                {row.name}
            </span>
            {showLedger ? (
                <span className={styles.childCount}>{row.ledger_code}</span>
            ) : null}
            {row.is_group ? (
                <span className={styles.childCount}>
                    {row.children_count}{' '}
                    {row.children_count === 1 ? 'sub-account' : 'sub-accounts'}
                </span>
            ) : null}
        </span>
    );
}

interface AccountsTableProps {
    rows: AccountRow[];
    totalFiltered: number;
    totalAll: number;
    collapsedIds: string[];
    onToggleCollapse: (id: string) => void;
    onExpandAll: () => void;
    onCollapseAll: () => void;
    onToggleStatus: (id: string) => void;
    onView: (row: AccountRow) => void;
    onEdit: (row: AccountRow) => void;
    onDelete: (row: AccountRow) => void;
    filtersActive: boolean;
    onClearFilters: () => void;
    loading: boolean;
    /** Shows each row's ledger code while all ledgers are visible. */
    showLedger: boolean;
}

export function AccountsTable({
    rows,
    totalFiltered,
    totalAll,
    collapsedIds,
    onToggleCollapse,
    onExpandAll,
    onCollapseAll,
    onToggleStatus,
    onView,
    onEdit,
    onDelete,
    filtersActive,
    onClearFilters,
    loading,
    showLedger,
}: AccountsTableProps) {
    const columns = useMemo<DataColumn<AccountRow>[]>(
        () => [
            { key: 'code', label: 'Code', width: 92 },
            {
                key: 'name',
                label: 'Account name',
                sortable: false,
                render: (_value, row) => (
                    <AccountName
                        row={row}
                        collapsed={collapsedIds.includes(row.id)}
                        showLedger={showLedger}
                        onToggle={() => onToggleCollapse(row.id)}
                    />
                ),
            },
            {
                key: 'type',
                label: 'Type',
                render: (value, row) => (
                    <Badge tone={TYPE_TONE[row.type]} size="sm">
                        {String(value)}
                    </Badge>
                ),
            },
            {
                key: 'balance_type',
                label: 'Normal balance',
                render: (value) => (
                    <span className={styles.normal}>
                        {capitalize(String(value))}
                    </span>
                ),
            },
            {
                key: 'status',
                label: 'Status',
                render: (value, row) => (
                    <Badge
                        tone={row.status === 'active' ? 'success' : 'neutral'}
                        size="sm"
                    >
                        {capitalize(String(value))}
                    </Badge>
                ),
            },
            {
                key: 'balance',
                label: 'Balance',
                width: 168,
                render: (value) => {
                    const amount = value as number;
                    return (
                        <span
                            className={amount < 0 ? styles.negative : undefined}
                        >
                            {formatNGN(amount)}
                        </span>
                    );
                },
            },
            {
                key: 'actions',
                label: 'Actions',
                sortable: false,
                render: (_value, row) => (
                    <DropdownMenu
                        label="Actions"
                        icon={<Ellipsis size={15} aria-hidden="true" />}
                        items={[
                            {
                                label: 'View',
                                icon: <Eye size={15} aria-hidden="true" />,
                                onSelect: () => onView(row),
                            },
                            {
                                label: 'Edit',
                                icon: <Pencil size={15} aria-hidden="true" />,
                                onSelect: () => onEdit(row),
                            },
                            row.status === 'active'
                                ? {
                                      label: 'Deactivate',
                                      icon: (
                                          <Ban size={15} aria-hidden="true" />
                                      ),
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () => onToggleStatus(row.id),
                                  }
                                : {
                                      label: 'Activate',
                                      icon: (
                                          <CircleCheck
                                              size={15}
                                              aria-hidden="true"
                                          />
                                      ),
                                      separatorBefore: true,
                                      onSelect: () => onToggleStatus(row.id),
                                  },
                            {
                                label: 'Delete',
                                icon: <Trash size={15} aria-hidden="true" />,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () => onDelete(row),
                            },
                        ]}
                    />
                ),
            },
        ],
        [
            collapsedIds,
            onToggleCollapse,
            onToggleStatus,
            onView,
            onEdit,
            onDelete,
            showLedger,
        ],
    );

    return (
        <section aria-labelledby="accounts-heading">
            <div className={styles.sectionHead}>
                <div>
                    <h2 id="accounts-heading" className={styles.sectionTitle}>
                        Accounts
                    </h2>
                    <p className={styles.count}>
                        {filtersActive
                            ? `${totalFiltered} of ${totalAll} accounts`
                            : `${totalAll} accounts`}
                    </p>
                </div>
                <div className={styles.sectionActions}>
                    {filtersActive ? (
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={onClearFilters}
                        >
                            Clear filters
                        </Button>
                    ) : null}
                    <Button variant="ghost" size="sm" onClick={onExpandAll}>
                        Expand all
                    </Button>
                    <Button variant="ghost" size="sm" onClick={onCollapseAll}>
                        Collapse all
                    </Button>
                </div>
            </div>

            <div className={styles.tableWrap}>
                {totalFiltered === 0 && !loading ? (
                    <EmptyState
                        icon={<SearchX size={24} />}
                        title="No accounts match these filters"
                        description="Try a shorter search or clear the filters to see the full chart of accounts."
                        action={
                            <Button
                                variant="secondary"
                                onClick={onClearFilters}
                            >
                                Clear filters
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <Skeleton
                            label="Loading chart of accounts"
                            lines={6}
                            loading={loading}
                        >
                            <SortableDataTable
                                rows={rows}
                                columns={columns}
                                rowKey="id"
                                caption="Chart of accounts"
                                emptyMessage="No accounts to show"
                                defaultSort={{ key: 'code', direction: 'asc' }}
                                itemName={{
                                    one: 'account',
                                    other: 'accounts',
                                }}
                            />
                        </Skeleton>
                    </>
                )}
            </div>
        </section>
    );
}
