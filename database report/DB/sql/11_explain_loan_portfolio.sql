-- 11_explain_loan_portfolio.sql
-- Execution plan for the loan portfolio aggregate query.

explain (analyze, buffers, format text)
select
    l.loan_no,
    c.customer_no,
    p.code as product_code,
    l.principal,
    l.total_repayable,
    l.status,
    l.maturity_date,
    coalesce(
        sum(
            s.total_due
            - s.principal_paid
            - s.interest_paid
            - s.fee_paid
            - s.penalty_paid
        ),
        0
    ) as outstanding
from loans l
join loan_customers c on c.id = l.loan_customer_id
join loan_products p on p.id = l.loan_product_id
left join loan_schedules s on s.loan_id = l.id
where l.deleted_at is null
group by
    l.id,
    l.loan_no,
    c.customer_no,
    p.code,
    l.principal,
    l.total_repayable,
    l.status,
    l.maturity_date,
    l.created_at
order by l.created_at desc;
