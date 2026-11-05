# SmartInventory — Multi-Warehouse Inventory for Small Businesses

An open-source, configurable inventory application built on **PHP 8.2+, MySQL 8+, HTML, Bootstrap 5 and vanilla JavaScript**. It records an immutable stock ledger rather than simply overwriting quantities.

## Features

- Email/password login and admin, operator and viewer roles.
- Products with SKU, unit and reorder threshold; multiple warehouses.
- Stock receipts, issues, opening balances and signed adjustments.
- Atomic inter-warehouse transfers (both ledger entries commit together).
- Lot/batch reference and optional expiry date.
- Dashboard for current stock, low-stock and expiring lots.
- CSV export of stock balances and movement audit trail.
- CSRF tokens, parameterized SQL, transaction-based updates and stock non-negativity validation.
- Responsive Bootstrap dashboard and free MIT license.

## Quick start

1. Create a blank MySQL 8+ database and a least-privilege database user.
2. Import `database/schema.sql` via phpMyAdmin or MySQL CLI.
3. Copy `.env.example` to `.env` and configure DB connection. Keep `.env` outside public web root.
4. Configure your domain to serve **only** the `public/` folder. PHP extensions: `pdo_mysql`, `mbstring`, `openssl`.
5. Create a first administrator from CLI: `php scripts/create-admin.php "Admin" admin@example.org "replace-with-a-long-password"`. Avoid passing real passwords on shared shells.
6. Sign in and create warehouses and products; record opening balances before issuing stock.
7. For local demo: `php -S 127.0.0.1:8000 -t public` (web server only; database is still required).

### Operations

Receipt / opening entries add stock. Issues remove stock. An adjustment may be positive or negative, but cannot create a negative lot balance. Transfers move stock atomically without changing global totals. To correct a mistake, create a compensating entry; do not edit ledger rows.

All quantities support **3 decimal places**. Use `.` as the decimal separator, e.g. `2.500` = 2.5 units. Expiry dates are tracked per movement and are advisory; records should be checked physically and lot-wise. CSV files contain user-generated text; review them before opening in spreadsheet software.

### Security / production notes

The application is a reference implementation, not a certified ERP or accounting package. Before business-critical deployment, test all flows on MySQL with your versions, configure HTTPS (`SESSION_SECURE=1`), a firewall, database backups, rate limiting at the reverse proxy, permissions, retention rules and operational monitoring. Protect credentials; do not publish `.env`. Imports, barcode scanning and multi-company tenancy are not included in v1. No fiscal invoices or tax certification are provided.

### Tests

```bash
find public src scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/domain_test.php
```

No live database integration test was run in the initial package environment.

## Open source & donations

**License:** MIT. Contributions: issues and pull requests are welcome. Donations are **voluntary** and never unlock features.

- **RedotPay ID:** `1612817131`
- **Airtm email:** `santosantonio.eng@gmail.com`
- **Website:** https://santosantonio.it.ao/

See [SUPPORT.md](SUPPORT.md).
