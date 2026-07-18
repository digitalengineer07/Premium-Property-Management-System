# Implementation Plan: End-to-End Security for Payment Amounts (Partial Payment Loophole Fix)

## Goal
To eliminate a critical security and logical vulnerability where the Admin Approval system blindly trusts the incoming payment amount and marks entire bills as `Paid` even if the renter manipulated the amount or submitted a partial payment. This will ensure perfect mathematical integrity across the ledger.

## The Security Loophole
Currently, in `payment-verifications.php`, if an admin approves a payment notification, the backend executes a blind SQL update like:
`UPDATE rent SET status='Paid' WHERE id=$bid`
This happens **regardless of the amount the user submitted**. 
A renter could technically submit a payment for ₹10 against a ₹20,000 bill. If the admin hits "Approve" (perhaps thinking it's a partial payment), the system instantly wipes the entire ₹20,000 debt. 
Furthermore, for `total` or `monthly` payments, a partial payment would blindly wipe out **all** of the user's pending bills.

## Proposed Fixes

### 1. Secure Specific Bill Updates (Rent & Electricity)
Instead of hardcoding `status='Paid'`, the system will now mathematically calculate the status:
1. When approved, insert the exact submitted amount into the `payments` ledger.
2. Query the `payments` ledger for the **total sum** paid towards that specific `bill_id`.
3. Query the `rent` or `electricity` table for the **actual required amount**.
4. If `total_sum_paid >= actual_required_amount`, set `status='Paid'`.
5. If `total_sum_paid < actual_required_amount`, set `status='Partial'`.

### 2. Auto-Allocation for Bulk Payments (Total & Monthly)
If a user submits a partial payment towards their "Total Outstanding" balance:
1. Fetch all unpaid/partial bills for the user, sorted by oldest first (chronological order).
2. Mathematically distribute the approved payment amount across these bills one by one.
3. As each bill is fully covered, mark it as `Paid`. If the remaining payment amount only covers part of a bill, mark that bill as `Partial`.
4. Generate corresponding specific entries in the `payments` ledger so every rupee is perfectly accounted for.

## Files to Modify
- `admin/payment-verifications.php` (The core logic rewrite)
- `admin/allocate_payment.php` (We will utilize this empty file to house the complex auto-allocation algorithm to keep the code clean).

## Verification Plan
1. Simulate a partial payment for a specific bill from the user panel (e.g. pay ₹500 for a ₹2000 bill). Approve it and ensure the bill status updates to `Partial`, not `Paid`.
2. Simulate a partial payment on the "Total Outstanding" balance. Approve it and ensure the payment correctly cascades down, paying off the oldest bills first and leaving the newest bills as `Due` or `Partial`.

> [!CAUTION]
> This is a critical security and financial logic rewrite. Please approve so I can immediately lock down this vulnerability.
