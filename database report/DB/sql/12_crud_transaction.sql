-- 12_crud_transaction.sql
-- A complete create, read, update and delete sequence.
-- Everything runs inside a transaction and is rolled back, so the data
-- in the database is not changed when the script finishes.

begin;

-- Remove any leftover demonstration row before inserting.
delete from journal_transactions
where id = '00000000-0000-0000-0000-000000000001'
   or ref = 'TXN-DEMO-0001';

-- Create
insert into journal_transactions
    (id, description, ref, status, effective_date, created_at, updated_at)
values
    (
        '00000000-0000-0000-0000-000000000001',
        'Raw SQL demonstration transaction',
        'TXN-DEMO-0001',
        'DRAFT',
        current_date,
        now(),
        now()
    );

-- Read
select id, description, ref, status, effective_date
from journal_transactions
where id = '00000000-0000-0000-0000-000000000001';

-- Update
update journal_transactions
set description = 'Raw SQL demonstration transaction (updated)',
    updated_at = now()
where id = '00000000-0000-0000-0000-000000000001';

-- Read the updated row
select id, description, ref, status, effective_date, updated_at
from journal_transactions
where id = '00000000-0000-0000-0000-000000000001';

-- Delete
delete from journal_transactions
where id = '00000000-0000-0000-0000-000000000001';

-- Confirm the row is gone
select count(*) as remaining_rows
from journal_transactions
where id = '00000000-0000-0000-0000-000000000001';

rollback;
