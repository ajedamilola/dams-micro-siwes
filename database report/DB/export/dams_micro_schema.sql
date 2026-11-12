--
-- PostgreSQL database dump
--

\restrict m6fcIA5HSZdT1tRBO1wPSmSqWZRKRF7FeHDwe7fyiIXlTQ7NZllxYex3Y3WCBmx

-- Dumped from database version 18.1 (Ubuntu 18.1-1.pgdg25.10+2)
-- Dumped by pg_dump version 18.1 (Ubuntu 18.1-1.pgdg25.10+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_loan_product_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_loan_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_disbursement_journal_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_disbursement_account_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_company_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_schedules DROP CONSTRAINT IF EXISTS loan_schedules_loan_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_repayments DROP CONSTRAINT IF EXISTS loan_repayments_payment_account_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_repayments DROP CONSTRAINT IF EXISTS loan_repayments_loan_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_repayments DROP CONSTRAINT IF EXISTS loan_repayments_journal_transaction_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_products DROP CONSTRAINT IF EXISTS loan_products_company_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_product_accounts DROP CONSTRAINT IF EXISTS loan_product_accounts_loan_product_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_product_accounts DROP CONSTRAINT IF EXISTS loan_product_accounts_account_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_company_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_account_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_customer_documents DROP CONSTRAINT IF EXISTS loan_customer_documents_loan_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_accruals DROP CONSTRAINT IF EXISTS loan_accruals_loan_schedule_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_accruals DROP CONSTRAINT IF EXISTS loan_accruals_loan_id_foreign;
ALTER TABLE IF EXISTS ONLY public.loan_accruals DROP CONSTRAINT IF EXISTS loan_accruals_journal_transaction_id_foreign;
ALTER TABLE IF EXISTS ONLY public.ledgers DROP CONSTRAINT IF EXISTS ledgers_company_id_foreign;
ALTER TABLE IF EXISTS ONLY public.accounts DROP CONSTRAINT IF EXISTS accounts_ledger_id_foreign;
ALTER TABLE IF EXISTS ONLY public.account_opening_balances DROP CONSTRAINT IF EXISTS account_opening_balances_account_id_foreign;
DROP INDEX IF EXISTS public.sessions_user_id_index;
DROP INDEX IF EXISTS public.sessions_last_activity_index;
DROP INDEX IF EXISTS public.loans_loan_product_id_index;
DROP INDEX IF EXISTS public.loans_loan_customer_id_index;
DROP INDEX IF EXISTS public.loans_company_id_status_index;
DROP INDEX IF EXISTS public.loan_schedules_loan_id_due_date_index;
DROP INDEX IF EXISTS public.loan_repayments_loan_id_payment_date_index;
DROP INDEX IF EXISTS public.loan_products_company_id_status_index;
DROP INDEX IF EXISTS public.loan_product_accounts_loan_product_id_index;
DROP INDEX IF EXISTS public.loan_customers_company_id_status_index;
DROP INDEX IF EXISTS public.loan_customers_company_id_kyc_status_index;
DROP INDEX IF EXISTS public.loan_customer_documents_loan_customer_id_doc_type_index;
DROP INDEX IF EXISTS public.loan_accruals_loan_id_accrual_date_index;
DROP INDEX IF EXISTS public.jobs_queue_index;
DROP INDEX IF EXISTS public.failed_jobs_connection_queue_failed_at_index;
DROP INDEX IF EXISTS public.cache_locks_expiration_index;
DROP INDEX IF EXISTS public.cache_expiration_index;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_unique;
ALTER TABLE IF EXISTS ONLY public.system_settings DROP CONSTRAINT IF EXISTS system_settings_pkey;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.migrations DROP CONSTRAINT IF EXISTS migrations_pkey;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_pkey;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_loan_no_unique;
ALTER TABLE IF EXISTS ONLY public.loans DROP CONSTRAINT IF EXISTS loans_company_id_loan_no_unique;
ALTER TABLE IF EXISTS ONLY public.loan_schedules DROP CONSTRAINT IF EXISTS loan_schedules_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_schedules DROP CONSTRAINT IF EXISTS loan_schedules_loan_id_installment_no_unique;
ALTER TABLE IF EXISTS ONLY public.loan_repayments DROP CONSTRAINT IF EXISTS loan_repayments_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_products DROP CONSTRAINT IF EXISTS loan_products_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_products DROP CONSTRAINT IF EXISTS loan_products_company_id_code_unique;
ALTER TABLE IF EXISTS ONLY public.loan_product_accounts DROP CONSTRAINT IF EXISTS loan_product_accounts_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_product_accounts DROP CONSTRAINT IF EXISTS loan_product_accounts_loan_product_id_operation_type_unique;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_customer_no_unique;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_company_id_phone_unique;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_company_id_nin_unique;
ALTER TABLE IF EXISTS ONLY public.loan_customers DROP CONSTRAINT IF EXISTS loan_customers_company_id_bvn_unique;
ALTER TABLE IF EXISTS ONLY public.loan_customer_documents DROP CONSTRAINT IF EXISTS loan_customer_documents_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_accruals DROP CONSTRAINT IF EXISTS loan_accruals_pkey;
ALTER TABLE IF EXISTS ONLY public.loan_accruals DROP CONSTRAINT IF EXISTS loan_accruals_loan_schedule_id_accrual_date_unique;
ALTER TABLE IF EXISTS ONLY public.ledgers DROP CONSTRAINT IF EXISTS ledgers_pkey;
ALTER TABLE IF EXISTS ONLY public.journal_transactions DROP CONSTRAINT IF EXISTS journal_transactions_pkey;
ALTER TABLE IF EXISTS ONLY public.journal_entries DROP CONSTRAINT IF EXISTS journal_entries_pkey;
ALTER TABLE IF EXISTS ONLY public.jobs DROP CONSTRAINT IF EXISTS jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.job_batches DROP CONSTRAINT IF EXISTS job_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_uuid_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.eod_operations DROP CONSTRAINT IF EXISTS eod_operations_pkey;
ALTER TABLE IF EXISTS ONLY public.companies DROP CONSTRAINT IF EXISTS companies_pkey;
ALTER TABLE IF EXISTS ONLY public.cache DROP CONSTRAINT IF EXISTS cache_pkey;
ALTER TABLE IF EXISTS ONLY public.cache_locks DROP CONSTRAINT IF EXISTS cache_locks_pkey;
ALTER TABLE IF EXISTS ONLY public.accounts DROP CONSTRAINT IF EXISTS accounts_pkey;
ALTER TABLE IF EXISTS ONLY public.account_opening_balances DROP CONSTRAINT IF EXISTS account_opening_balances_pkey;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.system_settings ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.migrations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.journal_entries ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.failed_jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.eod_operations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.companies ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.account_opening_balances ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.system_settings_id_seq;
DROP TABLE IF EXISTS public.system_settings;
DROP TABLE IF EXISTS public.sessions;
DROP TABLE IF EXISTS public.password_reset_tokens;
DROP SEQUENCE IF EXISTS public.migrations_id_seq;
DROP TABLE IF EXISTS public.migrations;
DROP TABLE IF EXISTS public.loans;
DROP TABLE IF EXISTS public.loan_schedules;
DROP TABLE IF EXISTS public.loan_repayments;
DROP TABLE IF EXISTS public.loan_products;
DROP TABLE IF EXISTS public.loan_product_accounts;
DROP TABLE IF EXISTS public.loan_customers;
DROP TABLE IF EXISTS public.loan_customer_documents;
DROP TABLE IF EXISTS public.loan_accruals;
DROP TABLE IF EXISTS public.ledgers;
DROP TABLE IF EXISTS public.journal_transactions;
DROP SEQUENCE IF EXISTS public.journal_entries_id_seq;
DROP TABLE IF EXISTS public.journal_entries;
DROP SEQUENCE IF EXISTS public.jobs_id_seq;
DROP TABLE IF EXISTS public.jobs;
DROP TABLE IF EXISTS public.job_batches;
DROP SEQUENCE IF EXISTS public.failed_jobs_id_seq;
DROP TABLE IF EXISTS public.failed_jobs;
DROP SEQUENCE IF EXISTS public.eod_operations_id_seq;
DROP TABLE IF EXISTS public.eod_operations;
DROP SEQUENCE IF EXISTS public.companies_id_seq;
DROP TABLE IF EXISTS public.companies;
DROP TABLE IF EXISTS public.cache_locks;
DROP TABLE IF EXISTS public.cache;
DROP TABLE IF EXISTS public.accounts;
DROP SEQUENCE IF EXISTS public.account_opening_balances_id_seq;
DROP TABLE IF EXISTS public.account_opening_balances;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: account_opening_balances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.account_opening_balances (
    id bigint NOT NULL,
    date character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    balance numeric(20,4) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: account_opening_balances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.account_opening_balances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: account_opening_balances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.account_opening_balances_id_seq OWNED BY public.account_opening_balances.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.accounts (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    description character varying(255) NOT NULL,
    parent_id character varying(255),
    ledger_id uuid NOT NULL,
    subledger_id character varying(255),
    balance_type character varying(255) NOT NULL,
    display_balance numeric(20,4) DEFAULT '0'::numeric NOT NULL,
    category character varying(255) DEFAULT 'Uncategorized'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    CONSTRAINT accounts_balance_type_check CHECK (((balance_type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying])::text[])))
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: companies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.companies (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: companies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.companies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: companies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.companies_id_seq OWNED BY public.companies.id;


--
-- Name: eod_operations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.eod_operations (
    id bigint NOT NULL,
    status character varying(255) NOT NULL,
    date date NOT NULL,
    failure_reason character varying(255),
    logs json DEFAULT '[]'::json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT eod_operations_status_check CHECK (((status)::text = ANY ((ARRAY['STARTED'::character varying, 'RUNNING'::character varying, 'FAILED'::character varying, 'COMPLETED'::character varying])::text[])))
);


--
-- Name: eod_operations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.eod_operations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: eod_operations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.eod_operations_id_seq OWNED BY public.eod_operations.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: journal_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.journal_entries (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    value numeric(20,4) NOT NULL,
    balance_after numeric(20,4),
    journal_transaction_id uuid NOT NULL,
    account_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT journal_entries_type_check CHECK (((type)::text = ANY ((ARRAY['debit'::character varying, 'credit'::character varying])::text[])))
);


--
-- Name: journal_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.journal_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: journal_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.journal_entries_id_seq OWNED BY public.journal_entries.id;


--
-- Name: journal_transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.journal_transactions (
    id uuid NOT NULL,
    description character varying(255) DEFAULT ''::character varying NOT NULL,
    ref character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'DRAFT'::character varying NOT NULL,
    old_reversed_transaction_id character varying(255),
    posted_at timestamp(0) without time zone,
    effective_date timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT journal_transactions_status_check CHECK (((status)::text = ANY ((ARRAY['DRAFT'::character varying, 'SUBMITTED'::character varying, 'POSTED'::character varying, 'REVERSED'::character varying, 'CANCELLED'::character varying])::text[])))
);


--
-- Name: ledgers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ledgers (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    company_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_accruals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_accruals (
    id uuid NOT NULL,
    loan_id uuid NOT NULL,
    loan_schedule_id uuid,
    accrual_date date NOT NULL,
    interest_amount numeric(20,2) NOT NULL,
    journal_transaction_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_customer_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_customer_documents (
    id uuid NOT NULL,
    loan_customer_id uuid NOT NULL,
    doc_type character varying(255) NOT NULL,
    doc_number character varying(255),
    file_path character varying(255),
    verified_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_customers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_customers (
    id uuid NOT NULL,
    customer_no character varying(255) NOT NULL,
    company_id bigint NOT NULL,
    account_id uuid,
    customer_type character varying(255) DEFAULT 'individual'::character varying NOT NULL,
    first_name character varying(255) NOT NULL,
    middle_name character varying(255),
    last_name character varying(255) NOT NULL,
    business_name character varying(255),
    rc_number character varying(255),
    gender character varying(255),
    date_of_birth date,
    marital_status character varying(255),
    phone character varying(255) NOT NULL,
    phone_alt character varying(255),
    email character varying(255),
    address text NOT NULL,
    city character varying(255),
    state character varying(255),
    lga character varying(255),
    nationality character varying(255) DEFAULT 'Nigeria'::character varying NOT NULL,
    bvn character varying(11),
    nin character varying(11),
    means_of_id_type character varying(255),
    means_of_id_number character varying(255),
    id_expiry_date date,
    proof_of_address_type character varying(255),
    kyc_tier character varying(255) DEFAULT 'tier3'::character varying NOT NULL,
    kyc_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    risk_rating character varying(255) DEFAULT 'low'::character varying NOT NULL,
    is_pep boolean DEFAULT false NOT NULL,
    employment_status character varying(255),
    employer_name character varying(255),
    monthly_income numeric(20,2),
    next_of_kin_name character varying(255),
    next_of_kin_relationship character varying(255),
    next_of_kin_phone character varying(255),
    photo_path character varying(255),
    signature_path character varying(255),
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: loan_product_accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_product_accounts (
    id uuid NOT NULL,
    loan_product_id uuid NOT NULL,
    operation_type character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_products (
    id uuid NOT NULL,
    company_id bigint NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    interest_method character varying(255) DEFAULT 'flat'::character varying NOT NULL,
    annual_interest_rate numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    repayment_frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    min_principal numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    max_principal numeric(20,2) DEFAULT 999999999999.99 NOT NULL,
    min_tenor integer DEFAULT 1 NOT NULL,
    max_tenor integer DEFAULT 60 NOT NULL,
    processing_fee_rate numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    processing_fee_flat numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    penalty_rate_daily numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    penalty_grace_days integer DEFAULT 0 NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_repayments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_repayments (
    id uuid NOT NULL,
    loan_id uuid NOT NULL,
    amount numeric(20,2) NOT NULL,
    payment_date date NOT NULL,
    payment_account_id uuid NOT NULL,
    principal_component numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    interest_component numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    fee_component numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    penalty_component numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    journal_transaction_id uuid,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loan_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loan_schedules (
    id uuid NOT NULL,
    loan_id uuid NOT NULL,
    installment_no integer NOT NULL,
    due_date date NOT NULL,
    principal_due numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    interest_due numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    fee_due numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    penalty_due numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    total_due numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    principal_paid numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    interest_paid numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    fee_paid numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    penalty_paid numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    accrued_interest numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loans (
    id uuid NOT NULL,
    loan_no character varying(255) NOT NULL,
    company_id bigint NOT NULL,
    loan_product_id uuid NOT NULL,
    loan_customer_id uuid NOT NULL,
    principal numeric(20,2) NOT NULL,
    annual_interest_rate numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    interest_method character varying(255) DEFAULT 'flat'::character varying NOT NULL,
    repayment_frequency character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    tenor integer NOT NULL,
    processing_fee numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    total_interest numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    total_repayable numeric(20,2) DEFAULT '0'::numeric NOT NULL,
    disbursement_date date,
    maturity_date date,
    first_due_date date,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    approved_at timestamp(0) without time zone,
    disbursed_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    disbursement_account_id uuid,
    disbursement_journal_id uuid,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_settings (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    value json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: account_opening_balances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_opening_balances ALTER COLUMN id SET DEFAULT nextval('public.account_opening_balances_id_seq'::regclass);


--
-- Name: companies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies ALTER COLUMN id SET DEFAULT nextval('public.companies_id_seq'::regclass);


--
-- Name: eod_operations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eod_operations ALTER COLUMN id SET DEFAULT nextval('public.eod_operations_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: journal_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_entries ALTER COLUMN id SET DEFAULT nextval('public.journal_entries_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: account_opening_balances account_opening_balances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_opening_balances
    ADD CONSTRAINT account_opening_balances_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- Name: eod_operations eod_operations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.eod_operations
    ADD CONSTRAINT eod_operations_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: journal_entries journal_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_pkey PRIMARY KEY (id);


--
-- Name: journal_transactions journal_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.journal_transactions
    ADD CONSTRAINT journal_transactions_pkey PRIMARY KEY (id);


--
-- Name: ledgers ledgers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledgers
    ADD CONSTRAINT ledgers_pkey PRIMARY KEY (id);


--
-- Name: loan_accruals loan_accruals_loan_schedule_id_accrual_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_accruals
    ADD CONSTRAINT loan_accruals_loan_schedule_id_accrual_date_unique UNIQUE (loan_schedule_id, accrual_date);


--
-- Name: loan_accruals loan_accruals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_accruals
    ADD CONSTRAINT loan_accruals_pkey PRIMARY KEY (id);


--
-- Name: loan_customer_documents loan_customer_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customer_documents
    ADD CONSTRAINT loan_customer_documents_pkey PRIMARY KEY (id);


--
-- Name: loan_customers loan_customers_company_id_bvn_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_company_id_bvn_unique UNIQUE (company_id, bvn);


--
-- Name: loan_customers loan_customers_company_id_nin_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_company_id_nin_unique UNIQUE (company_id, nin);


--
-- Name: loan_customers loan_customers_company_id_phone_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_company_id_phone_unique UNIQUE (company_id, phone);


--
-- Name: loan_customers loan_customers_customer_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_customer_no_unique UNIQUE (customer_no);


--
-- Name: loan_customers loan_customers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_pkey PRIMARY KEY (id);


--
-- Name: loan_product_accounts loan_product_accounts_loan_product_id_operation_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_product_accounts
    ADD CONSTRAINT loan_product_accounts_loan_product_id_operation_type_unique UNIQUE (loan_product_id, operation_type);


--
-- Name: loan_product_accounts loan_product_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_product_accounts
    ADD CONSTRAINT loan_product_accounts_pkey PRIMARY KEY (id);


--
-- Name: loan_products loan_products_company_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_products
    ADD CONSTRAINT loan_products_company_id_code_unique UNIQUE (company_id, code);


--
-- Name: loan_products loan_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_products
    ADD CONSTRAINT loan_products_pkey PRIMARY KEY (id);


--
-- Name: loan_repayments loan_repayments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_repayments
    ADD CONSTRAINT loan_repayments_pkey PRIMARY KEY (id);


--
-- Name: loan_schedules loan_schedules_loan_id_installment_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_schedules
    ADD CONSTRAINT loan_schedules_loan_id_installment_no_unique UNIQUE (loan_id, installment_no);


--
-- Name: loan_schedules loan_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_schedules
    ADD CONSTRAINT loan_schedules_pkey PRIMARY KEY (id);


--
-- Name: loans loans_company_id_loan_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_company_id_loan_no_unique UNIQUE (company_id, loan_no);


--
-- Name: loans loans_loan_no_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_loan_no_unique UNIQUE (loan_no);


--
-- Name: loans loans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: loan_accruals_loan_id_accrual_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_accruals_loan_id_accrual_date_index ON public.loan_accruals USING btree (loan_id, accrual_date);


--
-- Name: loan_customer_documents_loan_customer_id_doc_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_customer_documents_loan_customer_id_doc_type_index ON public.loan_customer_documents USING btree (loan_customer_id, doc_type);


--
-- Name: loan_customers_company_id_kyc_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_customers_company_id_kyc_status_index ON public.loan_customers USING btree (company_id, kyc_status);


--
-- Name: loan_customers_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_customers_company_id_status_index ON public.loan_customers USING btree (company_id, status);


--
-- Name: loan_product_accounts_loan_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_product_accounts_loan_product_id_index ON public.loan_product_accounts USING btree (loan_product_id);


--
-- Name: loan_products_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_products_company_id_status_index ON public.loan_products USING btree (company_id, status);


--
-- Name: loan_repayments_loan_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_repayments_loan_id_payment_date_index ON public.loan_repayments USING btree (loan_id, payment_date);


--
-- Name: loan_schedules_loan_id_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loan_schedules_loan_id_due_date_index ON public.loan_schedules USING btree (loan_id, due_date);


--
-- Name: loans_company_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loans_company_id_status_index ON public.loans USING btree (company_id, status);


--
-- Name: loans_loan_customer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loans_loan_customer_id_index ON public.loans USING btree (loan_customer_id);


--
-- Name: loans_loan_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loans_loan_product_id_index ON public.loans USING btree (loan_product_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: account_opening_balances account_opening_balances_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.account_opening_balances
    ADD CONSTRAINT account_opening_balances_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: accounts accounts_ledger_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_ledger_id_foreign FOREIGN KEY (ledger_id) REFERENCES public.ledgers(id);


--
-- Name: ledgers ledgers_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ledgers
    ADD CONSTRAINT ledgers_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: loan_accruals loan_accruals_journal_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_accruals
    ADD CONSTRAINT loan_accruals_journal_transaction_id_foreign FOREIGN KEY (journal_transaction_id) REFERENCES public.journal_transactions(id) ON DELETE SET NULL;


--
-- Name: loan_accruals loan_accruals_loan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_accruals
    ADD CONSTRAINT loan_accruals_loan_id_foreign FOREIGN KEY (loan_id) REFERENCES public.loans(id) ON DELETE CASCADE;


--
-- Name: loan_accruals loan_accruals_loan_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_accruals
    ADD CONSTRAINT loan_accruals_loan_schedule_id_foreign FOREIGN KEY (loan_schedule_id) REFERENCES public.loan_schedules(id) ON DELETE SET NULL;


--
-- Name: loan_customer_documents loan_customer_documents_loan_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customer_documents
    ADD CONSTRAINT loan_customer_documents_loan_customer_id_foreign FOREIGN KEY (loan_customer_id) REFERENCES public.loan_customers(id) ON DELETE CASCADE;


--
-- Name: loan_customers loan_customers_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: loan_customers loan_customers_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_customers
    ADD CONSTRAINT loan_customers_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: loan_product_accounts loan_product_accounts_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_product_accounts
    ADD CONSTRAINT loan_product_accounts_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE RESTRICT;


--
-- Name: loan_product_accounts loan_product_accounts_loan_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_product_accounts
    ADD CONSTRAINT loan_product_accounts_loan_product_id_foreign FOREIGN KEY (loan_product_id) REFERENCES public.loan_products(id) ON DELETE CASCADE;


--
-- Name: loan_products loan_products_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_products
    ADD CONSTRAINT loan_products_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: loan_repayments loan_repayments_journal_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_repayments
    ADD CONSTRAINT loan_repayments_journal_transaction_id_foreign FOREIGN KEY (journal_transaction_id) REFERENCES public.journal_transactions(id) ON DELETE SET NULL;


--
-- Name: loan_repayments loan_repayments_loan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_repayments
    ADD CONSTRAINT loan_repayments_loan_id_foreign FOREIGN KEY (loan_id) REFERENCES public.loans(id) ON DELETE CASCADE;


--
-- Name: loan_repayments loan_repayments_payment_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_repayments
    ADD CONSTRAINT loan_repayments_payment_account_id_foreign FOREIGN KEY (payment_account_id) REFERENCES public.accounts(id) ON DELETE RESTRICT;


--
-- Name: loan_schedules loan_schedules_loan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loan_schedules
    ADD CONSTRAINT loan_schedules_loan_id_foreign FOREIGN KEY (loan_id) REFERENCES public.loans(id) ON DELETE CASCADE;


--
-- Name: loans loans_company_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_company_id_foreign FOREIGN KEY (company_id) REFERENCES public.companies(id) ON DELETE CASCADE;


--
-- Name: loans loans_disbursement_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_disbursement_account_id_foreign FOREIGN KEY (disbursement_account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: loans loans_disbursement_journal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_disbursement_journal_id_foreign FOREIGN KEY (disbursement_journal_id) REFERENCES public.journal_transactions(id) ON DELETE SET NULL;


--
-- Name: loans loans_loan_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_loan_customer_id_foreign FOREIGN KEY (loan_customer_id) REFERENCES public.loan_customers(id) ON DELETE CASCADE;


--
-- Name: loans loans_loan_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loans
    ADD CONSTRAINT loans_loan_product_id_foreign FOREIGN KEY (loan_product_id) REFERENCES public.loan_products(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict m6fcIA5HSZdT1tRBO1wPSmSqWZRKRF7FeHDwe7fyiIXlTQ7NZllxYex3Y3WCBmx

