<?php
require_once __DIR__ . '/accounting_store.php';
function accounting_migrate($conn): void {
    foreach ([
        ['users','rent_billing_mode',"ENUM('advance','arrears') NULL"],
        ['users','billing_mode_source','VARCHAR(100) NULL'],
        ['electricity','rent_month','VARCHAR(7) NULL'],
    ] as [$table,$column,$definition]) {
        if (!mysqli_num_rows(accounting_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'"))) accounting_query($conn, "ALTER TABLE $table ADD COLUMN $column $definition");
    }
    foreach (['payments'=>['total_amount'], 'rent'=>['rent_amount'], 'electricity'=>['amount']] as $table=>$columns) {
        foreach ($columns as $column) accounting_query($conn, "ALTER TABLE $table MODIFY $column DECIMAL(12,2) NOT NULL DEFAULT 0");
    }
    accounting_query($conn, "CREATE TABLE IF NOT EXISTS api_sessions (
        token_hash CHAR(64) PRIMARY KEY, account_id INT NOT NULL, role VARCHAR(20) NOT NULL,
        password_fingerprint CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(account_id,role), INDEX(expires_at)
    ) ENGINE=InnoDB");
    accounting_query($conn, "CREATE TABLE IF NOT EXISTS accounting_events (
        id BIGINT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(50) NOT NULL, user_id INT NULL,
        actor_id INT NULL, reference VARCHAR(100) NOT NULL, detail LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(reference)
    ) ENGINE=InnoDB");
    accounting_query($conn, "CREATE TABLE IF NOT EXISTS accounting_receipts (
        reference VARCHAR(100) PRIMARY KEY, user_id INT NOT NULL, received DECIMAL(12,2) NOT NULL,
        payment_mode VARCHAR(50) NOT NULL, external_reference VARCHAR(100) NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    accounting_query($conn, "CREATE TABLE IF NOT EXISTS accounting_reversals (
        original_reference VARCHAR(100) PRIMARY KEY, reversal_reference VARCHAR(100) NOT NULL UNIQUE,
        reason VARCHAR(500) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    // The application only appends corrections; old postings and audit evidence are retained.
    foreach (['payments','accounting_events','accounting_receipts','accounting_reversals'] as $table) {
        foreach (['UPDATE','DELETE'] as $operation) {
            $name = 'protect_' . $table . '_' . strtolower($operation);
            $found = accounting_query($conn, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='$name'");
            if (!mysqli_num_rows($found)) accounting_query($conn, "CREATE TRIGGER $name BEFORE $operation ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Accounting records are append-only; use a reversal'");
        }
    }
}
