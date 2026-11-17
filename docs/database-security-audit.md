# Database security audit

Scope covers local MariaDB metadata and application source. Access was read-only. Production hosting, firewall rules and live web-server enforcement require separate verification.

| Area | Evidence and finding |
| --- | --- |
| Database privileges | A local TCP connection succeeded as `root@localhost` without a supplied password. The runtime connector also defaults to root for local use unless overridden. Create a dedicated application account, verify its effective grants, and use a separate migration account. |
| Network and encryption | Port 3306 listens on `::`, MariaDB reports a blank bind address and TLS disabled. External reachability was not established. Bind to loopback if the app and database share a host. For a remote database, restrict network access and require verified TLS. |
| Credentials | The connection uses private configuration or environment variables. The current `db_credentials.php` is untracked. Historical exposure and secret rotation were not verified. A nested Git backup is tracked and should be excluded from deployment and reviewed for embedded credentials. |
| Backups and recovery | A local dump was restored to a test database during the accounting rollout. Apache deny rules exist for local backup files. Encryption, offsite copies, retention and production restore were not verified. Local binary logging is off, so a dump alone cannot recover transactions posted after it was taken. |
| Ledger integrity | Confirmed eight update/delete protection triggers across payments, events, receipt registry and reversals. MariaDB reported four foreign keys, all pointing to users from notification, request or query tables. The ledger still relies on application checks for payment and invoice relationships. |
| SQL construction | Prepared statements protect credential lookups and key payment writes. Legacy interpolation is still present across the application. A complete route-by-route injection and authorization review remains open. |
| Authorization and tenancy | Opaque API tokens are hashed and checked for expiry, revocation, role and account state. Earlier local tests confirmed role enforcement on selected endpoints. Admins share the property dataset. Multi-landlord isolation remains unfinished. |
| Session and CSRF | Login regenerates the browser session ID. The bootstrap sets HttpOnly and SameSite Lax. The admin move-out POST checks the session but lacks a CSRF token check. Verify Secure cookies on the live HTTPS host and review other write endpoints. |
| API token transport | The Authorization header is supported. A legacy query-string token fallback can expose credentials in request logs or history. Verify client compatibility before retiring it. |
| Error disclosure | Resident profile and multiple admin write routes can return raw database error details. Send generic errors to clients and keep internal details in protected logs. |
| Server monitoring | Local general query logging is off. Centralized database audit logs, alerts, storage encryption and the production patch level were not verified. |

Prioritize the database account and network settings first. Then close the identified request and error-handling gaps and verify host controls against the actual deployment.
