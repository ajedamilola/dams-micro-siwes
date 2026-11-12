-- 00_connection.sql
-- Confirms the database, user and server version used by this package.

select
    current_database() as database_name,
    current_user as database_user,
    current_setting('server_version') as server_version,
    now() as checked_at;
