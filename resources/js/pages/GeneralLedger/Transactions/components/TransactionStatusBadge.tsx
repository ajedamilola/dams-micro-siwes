import { Badge } from '@/components/arc/badge/badge';
import { capitalize } from '@/lib/format';
import { TRANSACTION_STATUS_TONE } from '@/lib/transactions';
import type { TransactionStatus } from '@/types/ledger';

export function TransactionStatusBadge({
    status,
}: {
    status: TransactionStatus;
}) {
    return (
        <Badge tone={TRANSACTION_STATUS_TONE[status]} size="sm">
            {capitalize(status)}
        </Badge>
    );
}
