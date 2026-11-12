-- 02_indexes.sql
-- Lists the indexes on the domain tables.

select
    tablename as table_name,
    indexname as index_name,
    indexdef as definition
from pg_indexes
where schemaname = 'public'
  and tablename in (
      'companies',
      'ledgers',
      'accounts',
      'journal_transactions',
      'journal_entries',
      'account_opening_balances',
      'eod_operations',
      'system_settings',
      'loan_customers',
      'loan_customer_documents',
      'loan_products',
      'loan_product_accounts',
      'loans',
      'loan_schedules',
      'loan_repayments',
      'loan_accruals'
  )
order by tablename, indexname;
