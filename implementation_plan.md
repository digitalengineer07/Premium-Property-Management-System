# Implementation Plan: Enterprise-Grade Financial Architecture (Phase 4)

## Goal
To elevate the system from a basic web application into a highly secure, enterprise-grade financial ledger. This requires sealing the final structural leaks in the system: centralizing manual payments, preventing ledger destruction on deletions, and automating credit applications.

## The 3 Critical Structural Flaws
1. **The "Two Brains" Problem (`mark-paid.php`)**: When you manually mark a bill as paid from the Admin panel, the system bypasses the new auto-allocator entirely and uses old legacy logic. This creates two separate sources of truth, which is dangerous for a financial system.
2. **Destructive Ledger Deletions (`delete-bill.php`)**: Currently, if you delete a bill that is marked as `Partial`, the system *permanently deletes the user's associated payment records*. This destroys ledger integrity and essentially erases the user's money. 
3. **Dead Credits (`advance_payment`)**: While the system correctly deposits overpayments into the user's `advance_payment` balance, there is no system to actually *use* that credit automatically when new bills are generated.

## Proposed Upgrades

### 1. Centralize the Financial Engine
- Rewrite `admin/mark-paid.php` so it funnels directly into the new `allocate_payment.php` engine. Whether a payment is approved via a user notification or manually recorded by the admin, it will pass through the exact same mathematical verifications.

### 2. Enforce Immutable Ledger Deletions
- Harden `admin/delete-bill.php`. It will now strictly block the deletion of **any** bill that is `Paid` or `Partial`. 
- Financial records with money attached to them should be immutable. A bill can only be deleted if its status is purely `Due` (no payments attached).

### 3. Automated Credit Application System
- Upgrade the bill generator (`save-bill.php`). 
- When you generate a new rent or electricity bill for a user, the system will immediately check if they have a positive `advance_payment` balance.
- If they do, the system will instantly apply their credit to the new bill (generating an automated internal payment ledger record) before it even reaches the renter's dashboard!

## Verification Plan
1. Manually mark a bill as paid via the Admin UI and verify it correctly routes through the auto-allocator.
2. Attempt to delete a `Partial` bill and verify the system blocks the destructive action.
3. Give a test user ₹1,000 in `advance_payment`, generate a new ₹2,500 bill, and verify the system instantly applies the credit and sets the bill to `Partial` (leaving exactly ₹1,500 Due).

> [!CAUTION]
> This is a deep architectural hardening. Please review the 3 upgrades above and approve this plan so I can finalize the system's enterprise transition.
