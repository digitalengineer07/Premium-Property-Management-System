# Premium Property Management System (RMS)

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-blue.svg)](#)
[![GitHub Repository](https://img.shields.io/badge/GitHub-Repository-181717?logo=github&logoColor=white)](https://github.com/digitalengineer07/Premium-Property-Management-System)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-5A0FC8?logo=pwa&logoColor=white)](#)

A high-performance, full-featured web and mobile-ready Property & Utility Management System built for property owners, landlords, and residents. It automates resident onboarding, electricity meter tracking, monthly combined bill generation, UPI QR payments, automated email reminders, ledger reconciliation, and document storage.

---

## 📌 Repository Link
**Official GitHub Repository:**  
👉 [digitalengineer07/Premium-Property-Management-System](https://github.com/digitalengineer07/Premium-Property-Management-System)

---

## 🌟 Key Highlights

- **Dual-Portal Architecture:** Dedicated portals for Property Administrator and Residents.
- **Dynamic Utility Billing Engine:** Automatic calculation of electricity units consumed (`Current Reading - Previous Reading`), multiplied by unit rate, combined with room rent, maintenance, and arrears.
- **Enterprise Payment Allocation Engine:** Handles partial payments, advance credits, security deposits, and FIFO-based outstanding debt settlement.
- **Seamless UPI Integration:** Generates dynamic NPCI-compliant UPI QR codes with pre-filled amounts and merchant references for instant payments.
- **Automated Communication:** SMTP-powered transactional emails for bill notices, OTP logins, and payment receipts using PHPMailer.
- **Security & KYC Vault:** Secure storage for Aadhaar identity proof and rental lease agreements with `.htaccess` direct-access protection.
- **PWA & Android TWA Support:** Installable on smartphones with offline caching via Service Worker and Android Trusted Web Activity (TWA) wrapper.

---

## 🛠️ Technology Stack

| Layer | Technology / Tool | Description |
|---|---|---|
| **Backend** | Core PHP (PHP 8.0+) | Modular procedural/OOP architecture with prepared statements |
| **Database** | MySQL 8.x / MariaDB | Relational database with InnoDB engine, foreign keys, and indexes |
| **Admin UI** | HTML5, CSS3, Vanilla JS | Custom responsive design system (`admin-design-system.css`), CSS Grid/Flexbox |
| **Resident Portal** | Mobile-First Responsive Web | Optimized touch UI, bottom navigation bar, card-based stats |
| **Iconography** | Boxicons 2.1 | Vector iconography for modern UI elements |
| **Authentication** | Native PHP Sessions & Bcrypt | Password hashing via `password_hash()` (Bcrypt), SMTP-based 6-digit OTP |
| **PDF Generation** | Dompdf Library | High-resolution PDF generation for rent slips and payment receipts |
| **Mailing / Alerts** | PHPMailer | Secure SMTP-based email notifications and automated cron reminders |
| **Automation** | PHP CLI Cron Tasks | Automated discrepancy checking, monthly billing jobs, and audit logs |
| **Mobile Integration** | PWA + Android TWA | Progressive Web App (`manifest.json`, `sw.js`) and Play Store AAB/APK package |
| **Version Control** | Git & GitHub | Modular branching strategy with feature branches |

---

## 📂 System Architecture & Modules

### 1. 🛡️ Admin Management Panel
- **Executive Dashboard:** Live metrics for total collected rent, outstanding arrears, electricity consumption, and active resident count.
- **Resident Directory:** Onboard new renters, manage room numbers, rent rates, meter IDs, and emergency contacts.
- **Utility & Billing Manager:** Add monthly meter readings; system calculates consumption, creates invoices, and updates individual renter ledgers.
- **Payment Verification Desk:** Review resident-submitted UPI UTR numbers and payment screenshots; one-click approval with instant receipt dispatch.
- **Master Ledger & Audit Trail:** Full financial log of every transaction, adjustment, and administrative action.
- **Announcement Board:** Publish high-priority notices visible on resident dashboards.

### 2. 📱 Resident Mobile Portal
- **Overview Dashboard:** Instant visibility of total dues, upcoming due dates, and electricity consumption history.
- **Instant UPI Checkout:** Scan dynamic QR code or click UPI intent link (GPay, PhonePe, Paytm) to pay exact billed amounts.
- **Payment Submission:** Submit transaction UTR number and proof screenshot directly from mobile.
- **Receipt & Invoice Vault:** View and download official PDF rent receipts and detailed breakdown slips.
- **Maintenance & Helpdesk:** Raise maintenance queries or discrepancies directly to property management.
- **Document Management:** Securely view uploaded lease agreement and KYC records.

---

## 🗄️ Database Schema Summary

The relational database (`madhav_kunj.sql`) consists of 16 structured tables:

| Table | Description |
|---|---|
| `admin` | Administrator credentials and authentication keys |
| `users` | Resident profiles, room allocations, base rents, and advance balances |
| `electricity` | Monthly meter readings, unit calculations, and consolidated bills |
| `payments` | Transaction logs, payment modes (UPI/Cash/Wallet), and audit statuses |
| `payment_requests` | Resident-initiated payment submissions awaiting admin verification |
| `payment_reminders` | Track automated reminder notices sent to defaulters |
| `meter_recharges` | Sub-meter electricity recharge history |
| `queries` | Resident complaints, service tickets, and resolution history |
| `announcements` | General and emergency notices for building residents |
| `audit_logs` | System security events, financial adjustments, and administrative actions |
| `login_logs` | Security logging of resident and admin authentication attempts |
| `password_resets` | Secure OTP tokens for password reset workflows |

---

## 🚀 Setup & Installation Guide

### Prerequisites
- **XAMPP / WAMP / LAMP** (PHP 8.0 or higher, MySQL 5.7+ or MariaDB 10.4+)
- **Apache Web Server** with `mod_rewrite` enabled
- **Composer** (optional, for updating Dompdf and PHPMailer dependencies)

### Local Development Setup
1. **Clone the Repository:**
   ```bash
   git clone https://github.com/digitalengineer07/Premium-Property-Management-System.git
   ```
2. **Move to Server Directory:**
   Copy the project folder into your XAMPP `htdocs` directory (e.g., `C:/xampp/htdocs/renter-system`).

3. **Import Database:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin/`
   - Create a new database named `u243132024_madhav_kunj` (or as defined in `db.php`).
   - Import `madhav_kunj.sql` into the newly created database.

4. **Configure Database Connection:**
   - Open `db_credentials.php` or `db.php` and verify credentials:
     ```php
     $servername = "localhost";
     $username   = "root";
     $password   = "";
     $dbname     = "u243132024_madhav_kunj";
     ```

5. **Configure SMTP Credentials (for Emails):**
   - Update `smtp_credentials.php` with your Gmail or SMTP relay credentials.

6. **Launch the Application:**
   - **Admin Login:** `http://localhost/renter-system/admin/login.php`
   - **Resident Portal:** `http://localhost/renter-system/login.php`

---

## 🔒 Security Best Practices Implemented

- **Prepared Statements (MySQLi):** Complete mitigation against SQL Injection across all user inputs.
- **Bcrypt Password Encryption:** Passwords stored as irreversible salted hashes.
- **Directory Protection:** `.htaccess` file security prohibiting public listing of sensitive files and scripts in `/uploads`.
- **Session Integrity:** Strict server-side session checks and role validation (`$_SESSION['admin']` vs `$_SESSION['user_id']`).
- **Cron Token Authentication:** Background scripts protected via `CRON_KEY` to prevent unauthorized execution.

---

## 📄 License & Attribution

- **Developer:** Nikhil ([@digitalengineer07](https://github.com/digitalengineer07))
- **Property:** Madhav Kunj Estate, Patna, Bihar.
- **Repository:** [Premium-Property-Management-System](https://github.com/digitalengineer07/Premium-Property-Management-System)