-- 05_customer_ledger.sql
-- Customer list with company, linked account and latest posted balance.

select
    lc.customer_no,
    trim(concat_ws(' ', lc.first_name, lc.middle_name, lc.last_name)) as customer_name,
    lc.customer_type,
    lc.kyc_tier,
    lc.kyc_status,
    lc.phone,
    co.name as company_name,
    a.code as account_code,
    a.status as account_status,
    (
        select je.balance_after
        from journal_entries je
        join journal_transactions jt on jt.id = je.journal_transaction_id
        where je.account_id = a.id
          and jt.status = 'POSTED'
        order by je.id desc
        limit 1
    ) as account_balance
from loan_customers lc
left join companies co on co.id = lc.company_id
left join accounts a on a.id = lc.account_id
where lc.deleted_at is null
order by lc.customer_no;
