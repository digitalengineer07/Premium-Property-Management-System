# Accounting repair and rollout

This change strengthens the existing open-invoice ledger. It is not a completed double-entry accounting package, a CA certification, or a multi-landlord release.

## Rules implemented

- Calculate money in integer paise; serialize decimal values at the database boundary. Do not forgive a one-paise shortfall.
- Deposit-only joining payments select arrears billing. A deposit plus the first rent creates a paid joining-rent invoice and selects advance billing. Security deposits do not pay utility invoices.
- Electricity and maintenance belong to the consumption month. Advance rent belongs to the next calendar month; arrears rent belongs to the consumption month. New combined invoices record `rent_month` separately.
- Outstanding invoices remain open across any number of months. New invoices must not rebill their balances as `dues`.
- Bulk receipts settle oldest invoices first, with excess retained as wallet credit. Invoice components settle electricity, rent, maintenance, extra charges, then historical opening dues.
- Wallet deduction, payment postings and status updates commit together or roll back together. Replaying a bulk receipt reference does not post it again.
- Resident dashboard, bills, payments and mobile balance totals, and admin outstanding totals use the shared ledger calculation. Negative deficit annotations are not receipts. Month-specific balances subtract each posting once.
- Profile editing cannot silently replace wallet balances. Residents with financial history cannot be deleted; invoices with payment history cannot be deleted.

## Confirmed causes in the previous code

1. Mobile monthly reporting added settled invoice totals to their payments, subtracting payments twice. Global reporting subtracted negative adjustments and omitted extra charges.
2. Bills/payments pages summed multiple rent invoices but subtracted payments for only one grouped invoice. They also counted electricity payments against rent a second time.
3. Wallet credit was set to zero before allocation, outside a transaction. Partial database failure could lose money or leave a partially posted receipt.
4. Payment approval was marked approved before its financial writes were guaranteed. Specific-bill approval compared total combined payments with one component.
5. One-paise payments/credits were discarded by `0.01` thresholds. Integer database columns could truncate fractional rupees.
6. Joining rent was recorded against bill ID zero, with no matching invoice; rent periods were not persisted. Invalid joining-payment validation did not stop resident creation.
7. Profile edits changed wallet amounts without receipts. Resident deletion purged financial history.

## Deployment order

1. Back up the real database and files. Run the audit against a restored copy first. The local production database was unavailable during this work; no real balances were altered or reconciled.
2. Run `php migrations/20261117_accounting.php` against the intended database. This adds billing mode and rent period fields and converts integer money columns to decimals. The script uses the application's database configuration; inspect that configuration first.
3. In Edit Resident, select advance or arrears for each existing resident based on their agreement and joining receipts. The migration deliberately does not guess. New invoices are blocked until this is selected.
4. Run `php tools/audit-accounting.php`. Set `AUDIT_DB_HOST`, `AUDIT_DB_PORT`, `AUDIT_DB_NAME`, `AUDIT_DB_USER`, and `AUDIT_DB_PASSWORD` as needed. This tool runs SELECTs in a read-only transaction and reports IDs, not personal details. Exit 2 means discrepancies need review.
5. Reconcile carried-forward dues, orphan joining receipts, stale statuses, old wallet transfers and manual adjustments against source receipts. Do not delete payments or zero dues automatically. Historical invoices and amounts are deliberately left untouched.
6. Test the existing desktop/mobile payment flows on the restored copy before production rollout.

## Validation

`php tests/accounting_test.php` runs database-independent rules. `php tests/accounting_database_test.php` uses only temporary tables on a dedicated localhost MariaDB instance on port 3308. It must never be pointed at production. Tests cover joining splits, period assignment, three-month balances, partial payments, one-paise allocations, receipt replay, wallet conservation, rollback and bill ownership.

## Remaining release blockers

- The application still has one shared property and unscoped admin access. Multi-landlord use needs landlord/property/tenancy ownership throughout queries, uploads, payment destinations, reports, authentication and authorization. It is not enabled by these changes.
- Mobile API authentication currently trusts unsigned base64 identity data, and some routes only test that a header exists. Replace this with server-validated expiring tokens across every API before exposing the app to other landlords. Embedded production credentials also need rotation and environment-based configuration.
- Legacy standalone bill creation and invoice-edit routes do not yet use the complete new billing validator. Restrict these during rollout; use Bill Generation for combined monthly invoices. A complete correction/reversal workflow, immutable audit records, period closing, bank reconciliation and deposit refunds remain to be built.
- Other historical reports still use stored status flags and bespoke cash/revenue formulas. Cash received, earned rent, refundable deposits and unearned advances must be reported separately before calling this a full accounting system.
- The QR workflow records a renter's claim of payment; admin approval remains necessary. Scanning a QR is not bank confirmation.

The existing theme and layouts are retained. The only added form control is the rent billing mode within the existing Edit Resident financial section; the wallet field is now read-only.
