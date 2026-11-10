# MFB Core System — Speaker notes

SIWES presentation. These notes are written in first person and match the presenter view in `index.html`. Press `P` in the deck to open the same notes.

---

## Slide 1 — Title

My name is Aje Damilola, and my matric number is 230591336. This is my SIWES presentation on DAMS Micro, an accounting and loan-management prototype I worked on as a Laravel backend developer. I will explain the problem, demonstrate the main workflows, and share what I learned. First, I will introduce Golden Edge Softech and my placement.

## Slide 2 — About the organisation

I completed my placement at Golden Edge Softech, a software development agency in Lagos, Nigeria. I worked in the Software Development Unit as a Core Backend Developer, using Laravel. My work focused on the backend logic for the project's accounting and loan-management workflows. That leads to the defined scope of my work.

## Slide 3 — Objectives and scope

SIWES gave me a way to apply software engineering to a finance problem. My built scope covers the general ledger, chart of accounts, control accounts and subledgers, loan customers and KYC, products, the loan lifecycle, reports, and business-date control. Deposits, transfers, and external settlement are outside this scope. Next, I will show the control risks this work addresses.

## Slide 4 — The problem

Manual processes rely on people to catch incomplete entries, reconcile separate loan records, and resolve unposted work before changing the date. These checks become harder as activity grows. I built explicit controls into the application to reduce that risk. The next slide shows how the modules work together.

## Slide 5 — The solution

The solution is DAMS Micro, presented here as an MFB Core System. Its connected modules are the general ledger, chart and control accounts, customer and KYC records, loan products and lifecycle, reports, and business-date control. Loan operations use the same accounting core as the general ledger. Next, I will show the architecture that separates these responsibilities.

## Slide 6 — System architecture

The application uses Laravel, React through Inertia, and PostgreSQL. My role focused on the Laravel backend, where workflow rules, journal posting and balance changes are handled. This separation keeps the user interface from becoming the source of accounting decisions. Next, I will explain the chart, control-account, and subledger structure.

## Slide 7 — Accounting backbone

The chart of accounts provides the financial structure, while control accounts summarise related detail. Customer accounts live in subledgers, and only leaf accounts can receive postings. On the right, a Bank Savings statement shows a posted movement and its running balance. Next, I will walk through that sample journal.

## Slide 8 — Transaction flow

This transaction detail is TXN-0026, a sample configured journal rather than a standard accounting prescription. It debits Bank Savings by 200,000 naira, credits Customer Account by 196,000 naira, and credits Loan Fee Income by 4,000 naira. The debit and credits total the same amount, which demonstrates double-entry balance for this configured example. Next, I will show the loan lifecycle and the separate interest-accrual process.

## Slide 9 — Products

The loan workflow covers customer and KYC records, product selection, booking, approval, disbursement, repayment, and closure. The loan-detail screenshot shows this lifecycle in the application. Interest accrual is a separate command; it is not performed automatically when the business date advances. Next, I will show the business-date control itself.

## Slide 10 — End of day

The business date is the financial clock used by the application. Advance Day locks that date, checks for draft and submitted journals, snapshots leaf-account balances, and moves to a selected later date in one database transaction. Interest accrual runs separately; this action is not a complete end-of-day workflow. Next, I will explain three design decisions behind the implementation.

## Slide 11 — Key design decisions

I store a running balance on each posted entry so current balances can be read quickly. A rebuild recalculates those balances in effective-date order when needed. External settlement remains outside the project, so loan events use selected internal cash, bank or clearing accounts. Next, I will show how posted entries appear in the reports.

## Slide 12 — Demo

These report screens include the chart of accounts, trial balance, and balance sheet. The selected screenshots show whether total debits and credits are equal in the displayed trial balance. That is evidence of balanced report output, not proof that every underlying business event is correct. Next, I will summarize the implementation challenges and the fixes.

## Slide 13 — Challenges and fixes

Three challenges shaped the implementation: maintaining running balances, keeping customer detail out of the main chart, and avoiding a partial business-date change. I addressed them with rebuild support, control accounts and subledgers, and one atomic date-change transaction. Each fix turns a financial risk into an explicit software rule. I will close with the skills I developed and the work still ahead.

## Slide 14 — Skills and conclusion

I strengthened my Laravel, PostgreSQL, React, and financial-domain design skills by turning workflow rules into tested application behavior. Future work includes audit logging, external settlement integration, and broader reconciliation testing. The core lesson is that clear accounting rules and controlled business dates need to shape the software from the start. I will end with a brief thank-you slide.

## Slide 15 — Thank you

Thank you for listening. This project helped me connect software engineering with accounting controls, loan workflows and financial reporting. I welcome questions about the accounting model, the implementation choices or what I would improve next.

---

## Before presenting

1. Confirm the Golden Edge Softech logo is visible on the organisation slide.
2. Confirm the actual presentation assets are present and legible: `gl-overview.png`, `gl-overview-full.png`, `account-statement.png`, `transaction-detail.png`, `loan-detail.png`, `advance-day.png`, `advance-day-dialog.png`, `chart-of-accounts.png`, `trial-balance.png`, and `balance-sheet.png`.
3. Check that each screenshot uses safe sample data and that its text is readable at presentation zoom.
4. Click each screenshot once to confirm it enlarges, then press `Esc` to confirm the enlarged view closes.
5. Open presenter notes and make one full slide pass to confirm the notes and visuals agree.
6. Print to PDF and confirm that all 15 slides occupy one landscape page each. The report slide prints both the trial balance and balance sheet.
