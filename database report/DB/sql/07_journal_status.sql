-- 07_journal_status.sql
-- Counts journal transactions by status.

select
    status,
    count(*) as transaction_count
from journal_transactions
group by status
order by status;
