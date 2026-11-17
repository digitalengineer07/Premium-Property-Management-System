# Accounting repair and rollout

## Local rollout — 18 November 2026

The changes were tested on a restored database and applied to the local `renter_system` database. No remote hosting deployment was performed. The existing theme and page layouts are retained.

A fresh private backup is `.rollout-backups/renter_system_20261118_before_schema.sql`. Do not publish or commit it. Before/after control totals agree: 10 residents, deposit profile total INR 50,000.00, wallet total INR 0.00, 53 combined invoices totaling INR 625,644.00, and 51 payment rows with `total_amount` sum INR 493,340.00. The payment control sum verifies preservation; it is not a cash-received report (which uses `paid_amount` and excludes internal transfers).

Four incorrect Paid labels on invoices 25, 44, 50 and 62 were changed to Partial from the receipt ledger, with an append-only audit event for each. A second reconciliation run found no changes. Historical invoice and payment amounts were preserved.

## Rules implemented

- Money uses integer paise and decimal storage. A one-paise shortfall remains due.
- Under the requested joining rule, one month's rent pays the security deposit and selects arrears; two months pay the deposit plus a real, paid joining-rent invoice and select advance. Deposits stay separate from rent/utilities. This assumes a deposit equal to one month's rent; other contractual arrangements need explicit configuration.
- Zero received leaves the mode unresolved until a joining payment is verified. Ambiguous amounts are rejected rather than guessed. Billing mode belongs to the account, never a hardcoded flat number or later payment delay.
- For January consumption, advance billing includes February rent with January electricity/maintenance; arrears includes January rent and utilities. Rent periods are stored separately.
- Electricity is (current reading minus previous reading) times unit rate. Validation checks meter continuity, month order, duplicate rent periods and nonnegative charges.
- Outstanding invoices remain open for any number of months; new invoices cannot rebill those balances as dues.
- Bulk receipts settle oldest invoices first; components settle electricity, rent, maintenance, extra charges, then historical dues. Excess stays as wallet credit. This is the application's documented allocation policy, not a statutory allocation rule.
- Posting, wallet movement and status changes commit atomically. Repeated submissions and reused bank references cannot create duplicate receipts.
- QR submissions require admin verification. Scanning a QR is not bank settlement confirmation.
- Reversals append correcting entries and a reason; originals are retained. Already-consumed wallet credit needs allocation reconciliation before reversal.
- Only the latest unpaid invoice without payment history can be edited. Financial-history deletion, direct wallet replacement and the destructive legacy database repair are blocked.

## Other corrections

Shared calculations drive resident balances, outstanding lists, principal dashboard totals and financial reports. Fixes address double-subtracted payments, incomplete multi-month balances, lost wallet credits and repeated carried balances in new invoices.

Reports distinguish cash received, invoice components paid, deposits and advances. Invented expenses, profit, growth and activity were removed. Supplier costs are not recorded, so profit is not fabricated.

API access now uses opaque server-validated tokens, expiry, role checks and logout revocation. Password changes invalidate tokens. Existing mobile users must sign in again; old unsigned tokens are rejected. Payment routes check ownership and authorization. Private configuration/environment variables supply database credentials and the signing secret. Preserve the existing signing secret for historical receipt verification.

## Historical records still requiring review

The audit reports 15 remaining flags:

- Ten existing accounts lack reliable recorded billing modes. Review agreements/joining receipts and set the mode in Edit Resident before generating bills. Automatic future onboarding cannot reconstruct missing historical facts.
- Five invoices contain carried dues: 44, 57, 64, 76 and 77. Compare against original invoices and receipts to determine duplication. No automatic write-off or deletion was performed.

The private audit is `.rollout-backups/accounting-audit-20261118.json`. Audit exit 2 means review flags, not migration failure. Balances containing these historical amounts remain subject to reconciliation.

## Verification

- 62 total unit/database checks passed, including 18 pure accounting checks: `php tests/accounting_database_test.php`. It selects `accounting_test` on localhost port 3306 (`ACCOUNTING_TEST_PORT` overrides) and uses temporary tables. Run on a development/test server only.
- 57 HTTP checks passed on `renter_rollout_test`: web login/pages, reports, both billing modes, duplicate invoice rejection, partial receipts, replay protection, resident balance agreement, token forgery, role checks and logout.
- 165 PHP files passed syntax checks; whitespace validation passed.
- Status reconciliation was tested on the clone first and verified idempotent.

The HTTP suite requires its synthetic fixture and loopback PHP server. `tests/prepare_rollout_fixture.php` is restricted to `renter_rollout_test`. Never restore this synthetic database over real accounts. These are functional tests, not visual browser or bank-integration tests.

## Deployment to another environment

1. Stop financial writes during a maintenance window. Back up the current code and database including triggers. Restore and verify a separate staging copy first.
2. Configure `RMS_ENV=production`, `RMS_DB_HOST`, `RMS_DB_PORT`, `RMS_DB_NAME`, `RMS_DB_USER`, `RMS_DB_PASSWORD`, and `RMS_PAYMENT_SECRET`, or use the private variables in `db_credentials.example.php`. Keep the existing signing secret; rotate exposed database credentials separately. HTTP Host headers never select the database environment.
3. Deploy matching code and run the explicit migration while writes remain stopped:

   ```text
   php migrations/20261117_accounting.php --database=TARGET_NAME --backup=ABSOLUTE_BACKUP_PATH
   ```

   Verify the printed target and financial control totals. The migration adds fields, decimal types, token/receipt/audit tables and append-only triggers. MariaDB DDL is not fully transactional; inspect failures before resuming traffic. Rerun only after resolving the cause.
4. Set `AUDIT_DB_HOST`, `AUDIT_DB_PORT`, `AUDIT_DB_NAME`, `AUDIT_DB_USER`, and `AUDIT_DB_PASSWORD` for the same target; run `php tools/audit-accounting.php`. Preview and apply derived status corrections only:

   ```text
   php tools/reconcile-statuses.php --database=TARGET_NAME
   php tools/reconcile-statuses.php --database=TARGET_NAME --apply --backup=ABSOLUTE_BACKUP_PATH
   ```

5. Resolve missing historic modes from evidence and reconcile carried dues. On staging, verify login, invoice generation, partial payment, repeated submission, reversal, downloads, reports and mobile re-login.
6. Verify web-server restrictions on credentials, includes, tests, tools, migrations and backups. Apache restrictions are included; other web servers need equivalent rules. Use HTTPS and appropriate database privileges. Keep development tooling outside public deployment where practical.
7. Resume writes after checks pass; monitor errors and rerun the audit after the first billing cycle.

## Recovery

Before new postings exist, restore matching pre-rollout code and database together during maintenance. After new receipts exist, restoring an older backup loses those receipts: stop writes, take another backup and reconcile/export new postings before any restore. Do not disable append-only triggers or use the retired database-sync repair to force a rollback.

## Release limits

This is a strengthened rental invoice/receipt ledger, not CA certification or a complete double-entry accounting package. Deposit refunds, period closing, bank reconciliation and a general journal remain outside this implementation.

Multi-landlord release is not enabled. Admins still share the current property's data. Landlord/property/tenancy ownership must be enforced across queries, files, payment destinations and reports before unrelated landlords can use it. Separate admin logins alone do not provide data isolation.
