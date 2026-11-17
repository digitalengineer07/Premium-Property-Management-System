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

## Follow-up after application hardening

Local Apache returned 403 for anonymous private document requests, direct database credential access and a nested Git backup path. The resident profile API returned 401 without authentication. These checks establish anonymous denial for the sampled routes. Authenticated cross-account access and live hosting remain unverified.

Source review confirmed the admin move-out action now checks a CSRF token, the document proxy compares exact stored paths, and the central API authentication helper ignores query-string tokens. The changed mobile source uses Authorization headers for the affected requests. Installed mobile builds and existing access log retention were not verified.

| Remaining finding | Evidence and impact |
| --- | --- |
| High: local database access | The local root connection still succeeds without a supplied password. MariaDB reports a blank bind address, TLS disabled and binary logging off. Firewall reachability and the production settings were not tested. |
| High before a shared launch: admin isolation | Admins still operate on the same renter dataset. Keep unrelated landlords out until tenant and property ownership are enforced across routes, reports and files. |
| Medium: deletions via GET | `admin/delete-bill.php` and `admin/delete-doc.php` mutate state via GET and put session CSRF tokens in the URL. Use POST forms and validate tokens in the body. |
| Medium: upload validation varies | Resident query and document uploads check filename extensions. Apply consistent server-side MIME checks, size limits and safe file handling across upload routes. |
| Medium: database error disclosure remains | `admin/save-bill.php` and `admin/reset-password.php` still have paths that return raw database errors. Keep diagnostic details in server logs and send safe client messages. |
| Needs runtime verification: login throttling | No throttling was identified in the reviewed web and API login handlers. Check proxy and host limits before treating the login routes as protected against repeated attempts. |
| Needs deployment verification: API CORS | Several API routes allow any browser origin. Check the intended clients and configured headers before narrowing the policy. Wildcard CORS alone does not bypass bearer authentication. |

All eight ledger protection triggers remain installed. The follow-up used read-only metadata checks and anonymous requests. It did not exercise destructive routes, perform a full authenticated authorization matrix or verify production controls.

## Application fixes after the follow-up

Bill and document deletion now require POST and a session CSRF token in the submitted form. Resident identity and query uploads use server-side MIME detection, a 5 MiB limit and an extension selected from the detected MIME type. The identity upload also requires a CSRF token. Apache denies common executable extensions under uploads. Bill generation and password reset now send generic database failure messages.

PHP syntax and the accounting unit checks passed. A full authenticated upload/deletion test and production verification remain open. Host-wide MariaDB changes and property-level isolation remain separate rollout work.
