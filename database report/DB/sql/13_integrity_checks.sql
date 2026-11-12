-- 13_integrity_checks.sql
-- Read-only checks for the seeded database.
-- Each query returns rows only when the condition it checks is found.

-- 1. Posted journals whose entries do not net to zero.
select
    jt.ref,
    sum(case when je.type = 'debit' then je.value else -je.value end) as net_amount
from journal_transactions jt
join journal_entries je on je.journal_transaction_id = jt.id
where jt.status = 'POSTED'
group by jt.id, jt.ref
having sum(case when je.type = 'debit' then je.value else -je.value end) <> 0;

-- 2. Journal entries whose transaction or account is missing.
select
    je.id,
    je.journal_transaction_id,
    je.account_id
from journal_entries je
left join journal_transactions jt on jt.id = je.journal_transaction_id
left join accounts a on a.id = je.account_id
where jt.id is null
   or a.id is null;

-- 3. Duplicate journal references.
select
    ref,
    count(*) as occurrences
from journal_transactions
group by ref
having count(*) > 1
order by ref;

-- 4. Duplicate account codes within a ledger.
select
    ledger_id,
    code,
    count(*) as occurrences
from accounts
group by ledger_id, code
having count(*) > 1
order by ledger_id, code;

-- 5. Duplicate opening-balance snapshots for the same account and date.
select
    account_id,
    "date",
    count(*) as occurrences
from account_opening_balances
group by account_id, "date"
having count(*) > 1
order by account_id, "date";

-- 6. Loans whose schedule totals differ from the loan header.
select
    l.loan_no,
    l.total_repayable,
    sum(s.total_due) as scheduled_total
from loans l
join loan_schedules s on s.loan_id = l.id
group by l.id, l.loan_no, l.total_repayable
having sum(s.total_due) <> l.total_repayable
order by l.loan_no;
