# DAMS Micro — User Guide for Accountants and Managers

> A plain-language guide to using DAMS Micro to run your books and your loan portfolio. No accounting-software jargon, no developer talk — just what you need to do your job correctly and pass audit.

---

## 1. What is DAMS Micro?

DAMS Micro is a **microfinance accounting and lending system** in one place. It replaces spreadsheets and disconnected loan trackers with two connected modules:

1.  **General Ledger (GL)** — your official books. Every naira in and out is recorded as a balanced double-entry journal. This is where accountants post, review, and report.
2.  **Loan Portfolio** — where you onboard borrowers, define loan products, book loans, disburse cash, and collect repayments. Every loan movement **automatically posts to the GL** — you never post loan journals by hand.

All amounts are in **Nigerian Naira (₦)**.

**Golden rule:** The GL is the single source of truth. Loans feed the GL. Reports read the GL. If it is not posted in the GL, it did not happen.

---

## 2. Who is this guide for?

| Role | What you do in DAMS Micro |
|------|---------------------------|
| **Accountant / Finance Officer** | Create chart of accounts, enter and post journals, export account statements, close each business day, run Trial Balance and Balance Sheet, record loan repayments, investigate imbalances. |
| **Branch / Credit Manager** | Onboard and verify customers (KYC), approve or reject loans, approve disbursements, monitor disbursement vs. collection, overdue loans, and portfolio health. Set product rules and limits. |
| **Auditor / Executive (read-only use)** | Review dashboards, Trial Balance as-at any date, Balance Sheet, loan schedules, and the full audit trail of who posted, approved, and reversed what. |

> Separation of duties is built in: a loan must be **booked → approved → disbursed** as separate steps. The person who books should not be the same person who approves.

---

## 3. Key ideas in plain language

### 3.1 Company
Everything belongs to a **Company** (e.g. your microfinance bank or branch entity). Customers, products, ledgers, and loans never mix across companies. A customer and the loan product you give them **must belong to the same company**.

### 3.2 Business Date — your financial clock
DAMS Micro does not use "today on the wall clock" for money. It uses a **Business Date** you control.

- All journals, disbursements, and repayments are dated **on or before the Business Date**. You cannot back-date into the future.
- You **advance the Business Date** (e.g. from Monday to Tuesday) only when all journals for the day are finished — **drafts and submitted-but-unposted journals must be cleared first**. This is your daily close control.
- Daily interest accruals and penalty calculations run **as of the Business Date**.

Think of it as: *"We have closed the books for Monday, now we open Tuesday."*

### 3.3 Ledgers and Chart of Accounts
- A **Ledger** is a big bucket (e.g. Main Ledger, Customers Subledger).
- An **Account** is a single line in your chart (e.g. `1001 - Cash in Vault`, `4200 - Interest Income`, `1300 - Loan Principal Receivable`).
- Accounts have a **normal balance**: `debit` (assets, expenses — e.g. cash, loans owed to you) or `credit` (liabilities, income, equity — e.g. deposits, interest earned).
- Accounts can be grouped under a **parent** (e.g. `1300 - Loans Receivable` → `1301 - Micro Loans`). Only **leaf accounts** (no children) accept postings.
- Each customer automatically gets their **own GL account** in the Customers subledger when onboarded, so you can see exactly what each borrower owes.

### 3.4 Journals — how money moves
Every financial event is a **Journal Transaction** with a reference like `TXN-0001`:

- A journal has **2 or more entries**: at least one **debit** and one **credit**.
- **Debits must equal credits** before you can submit. The system will block you otherwise.
- Lifecycle: `Draft → Submitted → Posted`. Posted is final. To fix a posted journal you **reverse** it — the system creates a new mirror journal linked to the original. Nothing is ever deleted silently, which is what auditors want to see.
- You can also **Cancel** a Draft or Submitted journal, or **Delete** a Draft.

### 3.5 Reports that managers trust
- **Account Statement**: every posting to one account in a period, with running balance. Exportable to Excel.
- **Trial Balance as-at a date**: list of all accounts with debit/credit balances. Total debits must equal total credits.
- **Balance Sheet as-at a date**: Assets = Liabilities + Equity (plus current-period surplus/deficit). Grouped under headings you configure in Settings.

### 3.6 Loan customers and KYC
A **Loan Customer** is an individual or business borrower. Each has a `CUS-XXXX` number, personal/business details, risk rating, and a **KYC status**: `pending → under_review → verified` (or `rejected`).

Nigeria CBN 3-tier KYC is built in:

- **Tier 1 – Low value**: BVN *or* NIN + basic info. Limits: ₦50,000 single deposit, ₦300,000 cumulative balance.
- **Tier 2 – Medium value**: BVN *and* NIN, verified. Limits: ₦100,000 single, ₦500,000 cumulative.
- **Tier 3 – Full KYC**: BVN *and* NIN + valid ID + verified proof of address + photo + signature. **Required for all loan customers.** No limits.

> You **cannot book a loan** for a customer whose KYC is not `verified` or who has no linked GL account.

### 3.7 Loan products — your lending rules
A **Loan Product** (e.g. `MICRO-6 — 6-Month Micro Loan`) is a reusable template that controls:

- **Interest method**: `Flat` (interest on original principal — higher, simpler) or `Reducing / Declining balance` (interest on remaining balance — fairer, EMI-style).
- **Annual rate, frequency** (weekly, biweekly, monthly), **min/max principal**, **min/max tenor** (number of instalments).
- **Processing fee %** (charged once, added to instalment 1) and **penalty**: % per day on overdue amounts after a grace period (e.g. 3 days).
- **Status**: only `active` products can book loans.
- **GL mapping (the 6 accounts)**: where each piece of a loan lives in your books:
  1. Principal Receivable, 2. Interest Receivable, 3. Interest Income, 4. Fee Income, 5. Penalty Receivable, 6. Penalty Income. All six must be mapped before the product can be used — the system blocks incomplete products.

### 3.8 Loans — lifecycle
Every loan has a number like `LN-2026-00001` and moves through:

`Pending Approval → Approved → Active (disbursed) → Closed`
with side paths `Rejected` and `Written Off`.

1.  **Book**: enter product, customer, principal, tenor, first due date. System checks product limits, generates the full repayment schedule, and calculates total interest + total repayable.
2.  **Approve / Reject**: manager decision. Only draft/pending loans can be decided.
3.  **Disburse**: pick a cash/bank settlement account and disbursement date. System posts the disbursement journal and the loan becomes `Active`.
4.  **Repay**: record cash received. System splits it automatically (see below) and posts the repayment journal.
5.  **Close**: automatic when schedules are fully paid. Overpayment is flagged as change to refund — it is not silently kept.

### 3.9 Repayment schedule and how payments are shared
At booking the system builds an **instalment schedule**: due date, principal due, interest due, fee due (instalment 1 only), penalty due (added later if late), total due, and status (`pending / partial / paid`).

When a repayment comes in, it is applied **oldest instalment first**, and within each instalment in this strict order:

**Penalty → Fee → Interest → Principal**

This is standard microfinance practice: fees and penalties are cleared before principal. If a customer pays less than one instalment, that instalment becomes `partial`. If they pay more, the remainder flows to the next instalment.

### 3.10 Interest accrual and penalties — what managers should know
- **Interest accrual** is the daily recognition of interest earned, even before the customer pays. It runs automatically per business date (idempotent — running twice does not double-count). It debits Interest Receivable and credits Interest Income.
- **Penalties** accrue only after the grace period, on the unpaid portion, at the product's daily rate. They are assessed automatically at repayment time and can be re-run safely — only the new delta posts.

---

## 4. Dashboards — your morning check

### General Ledger Overview (`/general-ledger`)
- **Posted transactions, Debit volume, Credit volume, Net movement** for the selected period.
- **Posting flow** (debits vs. credits per day), **Activity by ledger** (donut), **Transaction count**, **Accounts in motion** (highest activity), and **Recent postings**.
- Use the date-range filter to compare weeks/months. Net movement should hover near zero over time (debits = credits) — a persistent negative/positive net in a period is worth investigating.

### Loan Overview (`/loans`)
- **Principal disbursed, Repayments collected, Interest collected (with fees/penalties context), Outstanding portfolio, Overdue loans**.
- **Disbursements vs. collections per day** (are you collecting faster than you lend?), **Outstanding by product** (concentration risk), **Repayment mix** (how much of collections is principal vs. interest/fees/penalties), **Recent loans** and **Loan book status strip** (counts by status).
- Manager rule of thumb: watch **Outstanding**, **Overdue count**, and the **Pending Approvals** number every morning.

---

## 5. General Ledger — step-by-step for accountants

### 5.1 Chart of Accounts (`/general-ledger/accounts`)
- **View/search**: full list ordered by code, with ledger/company, balance type, category, and status.
- **Add account**: give code (unique per ledger), name, ledger, balance type (`debit`/`credit`), and category (e.g. Cash, Receivables, Revenue). To create a child, pick a parent — it inherits balance type and category.
- **Deactivate** instead of deleting when an account is obsolete. Only leaf, unused accounts can be removed.
- **Account page**: balance, history, and **Statement Export** to Excel for any date range — send this to auditors or customers.

### 5.2 Journals (`/general-ledger/transactions`) — the daily workflow
1.  **Create**: New Transaction → description (min. 5 characters, e.g. "Office rent March — head office") + effective date (≤ Business Date). You get a `TXN-XXXX` draft.
2.  **Add entries**: open the transaction → Add Entry → pick account → debit or credit + amount. Repeat until **total debits = total credits** and you have at least 2 entries.
3.  **Submit**: changes status to `Submitted`. The system validates balance. Only drafts can be edited.
4.  **Post**: posts to the books and updates balances instantly. Only submitted journals can be posted.
5.  **If wrong after posting**: use **Reverse** → enter reason/effective date. A new reversal journal is created and linked (`reverses / reversed by`). Never edit a posted journal directly.
6.  **If wrong before posting**: edit (draft only), **Cancel** (submitted), or **Delete** (draft only).

**Tips that prevent audit queries:**
- One economic event = one journal. Do not bundle rent, salaries, and adjustments in one `TXN`.
- Description should answer *what, who, why*: "Reversal of TXN-0042 — duplicate rent entry Feb" beats "correction".
- Effective date is the economic date, not the typing date.

### 5.3 Trial Balance (`/general-ledger/trial-balance`)
- Pick **As at** date (defaults to Business Date). Shows every account with a non-zero balance, debit and credit columns, and totals.
- Totals must match. If they do not, stop — do not advance the Business Date — and investigate unposted or reversed journals.
- Use it to answer: "What do we own/owe/earn/spend as of month-end?"

### 5.4 Balance Sheet (`/general-ledger/balance-sheet`)
- Same **As at** picker. Grouped into Current/Non-current Assets, Current/Non-current Liabilities, Equity, plus **Current period surplus/deficit** (revenue less expenses) so the sheet always balances.
- Headings come from **Settings → Balance Sheet Layout**. If your new account does not appear where expected, ask your administrator to map it to the right heading.
- Managers: compare month-to-month Assets Total vs. Liabilities Total vs. Equity Total. A growing surplus means the business earned more than it spent in the period.

### 5.5 Settings and Business Date (`/general-ledger/settings`)
- View/edit balance-sheet headings.
- **Advance Business Date**: enter the next date (forward only). Blocked if any Draft/Submitted journals exist — post, cancel, or delete them first. This is intentional: you cannot close a day with work hanging.
- Best practice: advance once per working day, after postings and loan accruals are done.

---

## 6. Loans — step-by-step

### 6.1 Customers (`/loans/customers`)
1.  **Create**: company, type (individual/business), name, phone, DOB, gender, address, KYC tier, BVN/NIN, ID type + proof of address for Tier 3. System assigns `CUS-XXXX` and auto-creates (or lets you link) the customer's GL account.
2.  **Upload documents**: ID, proof of address, photo, signature — stored against the customer for audit.
3.  **Review → Verify**: `pending → under_review → verified`. Only `verified` customers can borrow. `Rejected` customers are blocked with a reason.
4.  **Search** by name, number, or phone. Customer page shows linked GL account, KYC history, and all their loans.

> Accountant note: the customer's GL account is what loan journals post against. Never post manual journals to a customer account — let loan disbursement/repayment do it.

### 6.2 Products (`/loans/products`)
1.  **Create**: company, code (unique per company, e.g. `MICRO-6`), name, interest method (`flat` vs `reducing`), annual rate, frequency, principal/tenor ranges, fee %, penalty %/day + grace days.
2.  **Map the 6 GL accounts**: Principal/Interest/Penalty Receivable (debit-type asset accounts) and Interest/Fee/Penalty Income (credit-type revenue accounts). The system validates that each account has the correct balance type and category and belongs to the same company.
3.  **Activate**: set to `active`. Inactive products are hidden from booking but keep history.
4.  **Update mapping** anytime via the Mapping tab — future loans use the new accounts; past journals are untouched (correct audit behaviour).

**Choosing flat vs. reducing (for managers):**
- *Flat*: same interest every instalment. Simple to explain to borrowers. Effective cost is higher.
- *Reducing*: interest falls as principal is repaid. Fairer for longer tenors. Use for larger or longer loans.

### 6.3 Loans (`/loans/loans`) — from application to closure
1.  **Book**: Loans → New → product + verified customer (same company) + principal (within product min/max) + tenor (within min/max instalments) + first due date (after Business Date) + notes. System validates everything and shows the full schedule + total repayable **before you commit**. Status: `pending_approval`.
2.  **Approve or Reject**: manager opens the loan → Approve (records approval time) or Reject with reason. Only pending/draft loans can be decided.
3.  **Disburse**: pick an **active leaf cash/bank/clearing account** (e.g. "GTB Disbursement Account") + disbursement date (≤ Business Date). System posts: debit loan receivables, credit cash, and recognises fee income. Status becomes `active`, maturity date is set from the last schedule.
4.  **Collect repayment**: open active loan → Repay → amount + cash account + payment date (≤ Business Date) + notes. System first updates penalties to that date, then allocates FIFO (penalty → fee → interest → principal), posts the repayment journal, and records the split (principal/interest/fee/penalty components). If schedules hit zero, the loan auto-closes with a `closed_at` timestamp.
5.  **Monitor**: loan page shows outstanding balance, per-instalment paid vs. due, all repayments with journal links, and accrual history. Every repayment links to its `TXN-XXXX` — click through to see the GL impact.

**What the accountant sees for a loan (no manual work needed):**
- Disbursement: Dr Principal Receivable, Dr Fee Receivable (if any) / Cr Cash, Cr Fee Income.
- Daily accrual: Dr Interest Receivable / Cr Interest Income.
- Late penalty: Dr Penalty Receivable / Cr Penalty Income.
- Repayment: Dr Cash / Cr Principal/Interest/Fee/Penalty Receivable (per allocation).

### 6.4 Overdue and closure
- An **overdue loan** is an active loan with past-due instalments. The Loan Overview counts them prominently.
- Penalties accrue daily after grace days on the unpaid principal+interest+fee slice. Collecting triggers penalty assessment first, so the borrower always pays the true amount owed.
- Loans never disappear — `closed`, `rejected`, and `written_off` loans remain searchable for audit and portfolio history.

---

## 7. Daily and month-end routines

### Accountant — daily (15 minutes)
1.  Check GL Overview for today's postings; post or cancel any Drafts/Submitted journals.
2.  Record loan repayments received (Loans → Repay) with correct payment date and cash account.
3.  Confirm loan accruals ran for the Business Date (interest/penalty journals appear automatically).
4.  Advance Business Date only when unposted count is zero.

### Accountant — month-end
1.  Post all journals, advance Business Date to month-end.
2.  Export key account statements (cash, receivables, income) to Excel and file.
3.  Run Trial Balance as-at month-end → totals must match → save/print.
4.  Run Balance Sheet as-at month-end → review surplus/deficit → share with management.

### Manager — weekly
1.  Review Loan Overview: disbursed vs. collected trend, outstanding by product, overdue count, pending approvals.
2.  Approve/reject pending loans; verify KYC for flagged customers.
3.  Review products: are limits/rates still appropriate? Is any product over-concentrated?
4.  Spot-check 2–3 loan pages: schedule vs. repayments vs. linked journals.

---

## 8. Controls and safeguards (why auditors like this system)

- **Balanced journals enforced**: cannot submit unless debits = credits and ≥2 entries.
- **No silent edits**: drafts editable; submitted/posted locked; corrections via reversal with linkage.
- **Business Date gate**: no future-dated money; day cannot close with unposted work.
- **KYC gate**: unverified customers cannot borrow.
- **Product gates**: principal/tenor outside min/max blocked; inactive products blocked; incomplete 6-account mapping blocked.
- **Same-company enforcement**: customer, product, and GL accounts must align.
- **Cash-account validation**: disbursements/repayments only to active leaf cash/bank/clearing accounts — not to control or parent accounts.
- **Idempotent accruals**: re-running for the same date does not duplicate income.
- **Full trail**: every loan action links to its journal (`disbursement_journal_id`, `journal_transaction_id`); every reversal links both ways.

---

## 9. Worked example

> Adaeze (Tier 3, verified) applies for **₦500,000**, **6 monthly** instalments, product `MICRO-6`: 24% p.a. flat, 2% processing fee, 0.5%/day penalty after 3 days grace.

1.  Officer books: principal ₦500,000, tenor 6, first due date next month. System computes total interest = ₦500,000 × 24% × 6/12 = ₦60,000, fee = ₦10,000, total repayable = ₦570,000, 6 instalments of ~₦95,000 (fee on instalment 1).
2.  Manager approves. Officer disburses ₦500,000 from "GTB Disbursement" account. Adaeze receives ₦500,000 cash; books show ₦500,000 receivable + ₦10,000 fee receivable.
3.  Each business day, interest accrues proportionally to Interest Receivable/Income.
4.  Adaeze pays ₦95,000 on time → allocated fee+interest+principal of instalment 1, oldest first. Instalment 1 becomes `paid`.
5.  She misses instalment 2 by 5 days → 2 days of penalty (5 − 3 grace) accrue on the unpaid slice. Next payment clears penalty first, then fee/interest/principal.
6.  After 6 instalments (plus any penalties), outstanding hits zero → loan auto-closes. Trial Balance and Balance Sheet reflect all of it.

---

## 10. Glossary

| Term | Meaning |
|------|---------|
| Business Date | The financial date the system is operating on. Controls what can be posted and when. |
| Ledger / Account | Ledger = bucket; Account = single line in the chart of accounts. |
| Debit / Credit | Two sides of every journal. Assets/expenses increase with debits; liabilities/income/equity increase with credits. |
| Journal / TXN | One balanced financial event with 2+ entries. Reference like `TXN-0001`. |
| Draft / Submitted / Posted / Reversed | Journal states. Only Posted affects balances and reports. |
| Trial Balance | Debit/credit balances per account as-at a date. Must balance. |
| Balance Sheet | Assets vs. Liabilities + Equity as-at a date, with surplus/deficit. |
| KYC Tier | CBN identity assurance level (1/2/3). Tier 3 required to borrow. |
| Loan Product | Lending template: rate, method, frequency, limits, fees, penalties, GL mapping. |
| Schedule / Instalment | One row of what is due when on a loan. |
| Accrual | Recognising interest/penalty earned over time, before cash arrives. |
| FIFO allocation | Oldest debt and penalty→fee→interest→principal order for repayments. |
| Outstanding | What the borrower still owes across all schedules. |
| Overdue | Active loan with past-due instalments. |
| Disbursement / Repayment | Cash out to borrower / cash in from borrower, each auto-posted to the GL. |

---

## 11. FAQ

**Do I need to post loan journals manually?**
No. Booking, disbursement, repayment, accrual, and penalty all post automatically to the mapped GL accounts. Only non-loan events (rent, salaries, adjustments) need manual journals.

**Can I edit a posted journal?**
No — and that is by design. Reverse it with a reason. Both journals stay visible for audit.

**Why can't I advance the Business Date?**
Because Draft or Submitted journals are still open. Post, cancel, or delete them first, then advance.

**Why is my loan booking blocked?**
Common reasons: customer not KYC-verified, customer/product in different companies, principal/tenor outside product limits, product inactive, or product mapping incomplete (needs all 6 accounts).

**Why can't I pick a bank account for disbursement?**
It must be an active, leaf (no children), cash/bank/clearing account — not a parent, control, customer, or income account.

**What happens on overpayment?**
The system applies what is owed, closes the loan if nothing remains, and flags the excess as "Overpayment change" on the repayment note for refund. It does not keep the excess silently.

**Where do I see what a repayment did to the books?**
Open the loan → Repayments → click the journal link. You see the exact debit/credit entries.

---

*Document scope: General Ledger (accounts, journals, trial balance, balance sheet, business date, settings) and Loan Portfolio (customers/KYC, products/mapping, loans, schedules, repayments, accruals/penalties, overviews) as implemented in DAMS Micro. For access, permissions, or new-company setup, contact your system administrator.*
