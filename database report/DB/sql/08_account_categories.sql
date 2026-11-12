-- 08_account_categories.sql
-- Account count and cached display balance by category.

select
    category,
    count(*) as account_count,
    sum(display_balance) as total_display_balance
from accounts
group by category
order by account_count desc, category;
