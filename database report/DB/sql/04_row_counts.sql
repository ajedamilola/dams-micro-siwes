-- 04_row_counts.sql
-- Exact row counts for the domain tables.

select 'companies' as table_name, count(*) as row_count from companies
union all select 'ledgers', count(*) from ledgers
union all select 'accounts', count(*) from accounts
union all select 'journal_transactions', count(*) from journal_transactions
union all select 'journal_entries', count(*) from journal_entries
union all select 'account_opening_balances', count(*) from account_opening_balances
union all select 'eod_operations', count(*) from eod_operations
union all select 'system_settings', count(*) from system_settings
union all select 'loan_customers', count(*) from loan_customers
union all select 'loan_customer_documents', count(*) from loan_customer_documents
union all select 'loan_products', count(*) from loan_products
union all select 'loan_product_accounts', count(*) from loan_product_accounts
union all select 'loans', count(*) from loans
union all select 'loan_schedules', count(*) from loan_schedules
union all select 'loan_repayments', count(*) from loan_repayments
union all select 'loan_accruals', count(*) from loan_accruals
order by table_name;
