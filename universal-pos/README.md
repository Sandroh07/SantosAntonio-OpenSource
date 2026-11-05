# UniversalPOS — Open Source, Configurable Point of Sale

A lightweight **PHP 8.2+ / MySQL 8+ / Bootstrap 5** point-of-sale and business-operations system. Configure your business type instead of installing a different application for every trade.

![License](https://img.shields.io/badge/license-MIT-blue) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4) ![MySQL](https://img.shields.io/badge/MySQL-8%2B-4479A1)

## Features

- Business profiles: retail, restaurant, salon, pharmacy (commercial operations only), workshop, hardware, services, other.
- Product/service catalogue with SKU and barcode search; stock adjustments and low-stock alerts.
- Transactional checkout with server-validated pricing, inventory locking and atomic stock movements.
- Cash, externally processed card, bank transfer and mobile payment record types (no payment gateway integration).
- Customer directory, printable operational receipts, daily dashboard, date-filtered sales and CSV export.
- Administrator/cashier access roles, password hashing, session hardening, prepared SQL and CSRF validation.
- Configurable business name, currency, manually managed tax rate and receipt note.
- Voluntary donation details in the app and [`SUPPORT.md`](SUPPORT.md).

## Install (one business per deployment)

1. Set up PHP **8.2+** with `pdo_mysql`, `mbstring`, `openssl` and MySQL **8+** / compatible MariaDB. Configure HTTPS.
2. Create a database and dedicated least-privilege user. Import [`database/schema.sql`](database/schema.sql).
3. Copy `.env.example` to `.env` at the project root; update DB credentials. Never commit `.env`.
4. Run `php scripts/create-admin.php admin@example.com "Administrator"` in a private terminal. Use a password of 12+ characters.
5. Point the web server document root to the **`public/`** directory (not the repository root). Open the site, sign in, configure **Business setup**, create products and start a sale. Optionally import `database/demo_seed.sql` to load two clearly labelled demo catalog entries (never use demo data as production records).
6. Back up the MySQL database and review server access, logs, password policies and audit obligations before real deployment.

For cPanel, use an addon domain/subdomain with document root mapped to `universal-pos/public`. Ensure the `.env`, scripts and database dump are not web-accessible.

Check the database connection with `php scripts/doctor.php` on the server.

### Test

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/domain_test.php
```

## Architecture

`public/` is the only web-accessible directory; `src/` contains DB, authentication, view helpers, tax/amount logic and a transactional `createSale` routine. Database tables: users, settings, categories, products, customers, sales, sale_items and stock_movements.

Monetary values are **integer minor units** in MySQL. Tax is configured in basis points (1% = 100 bp), with half-up rounding to the nearest minor unit. Clients never supply authoritative unit prices.

## Important compliance and payment boundaries

**Not certified for fiscal invoicing in Angola (AGT).** See the [Angolan Ministry of Finance announcement on the phased e-invoicing rollout](https://www.minfin.gov.ao/sala-de-imprensa/noticias/noticia/facturacao-electronica-entra-em-vigor-a-partir-de-1-de-janeiro-de-2026). Printable receipts are **operational records only**, not a substitute for certified invoices or e-invoicing. Tax rates must be determined by the operator; do not infer current legal compliance from this software. Card/mobile payments are **recorded after external processing**, not charged by the software. Healthcare, food service and pharmacy-specific regulatory modules are not included. Conduct a full security, data-protection and legal audit before production use.

## Contributing and support

Issues, feature requests and pull requests are welcome. MIT licensed; no mandatory payments. Donation details and official owner website: [`SUPPORT.md`](SUPPORT.md).

**Project steward:** [Santos António](https://santosantonio.it.ao/) · `santosantonio.eng@gmail.com`
