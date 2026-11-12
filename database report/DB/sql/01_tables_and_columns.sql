-- 01_tables_and_columns.sql
-- Lists every column in the sixteen domain tables.

select
    c.table_name,
    c.ordinal_position,
    c.column_name,
    c.data_type,
    c.character_maximum_length,
    c.is_nullable,
    c.column_default
from information_schema.columns c
where c.table_schema = 'public'
  and c.table_name in (
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
order by c.table_name, c.ordinal_position;
