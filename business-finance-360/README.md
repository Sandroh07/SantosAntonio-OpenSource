# BusinessFinance 360 — Open-Source Management Finance

**PHP 8.2+ + MySQL 8+ + Bootstrap 5**, plus a standalone **Excel dashboard**. A lightweight financial operations system for small and medium businesses.

## Functional areas

- Company accounts with opening balances and calculated balances.
- Dated income/expense ledger, custom categories and references.
- Two-account transfers posted as a single database transaction; validates source balance.
- Monthly expense budgets by category and variance reporting.
- Dashboard of monthly receipts, spending, management net cashflow and account balances.
- Period closing/locking to prevent new backdated ledger entries, transfers and budget changes.
- CSV export, audit log, team roles (admin, accountant, viewer), admin creation via CLI.
- Separate Microsoft Excel workbook `spreadsheet/BusinessFinance_360_Dashboard.xlsx` for offline entry, budgets, KPIs, and a 12-month visual.
- All code and workbook are open source under MIT; voluntary donations do not unlock anything.

**Scope:** Internal cashflow and management reporting. This is **not certified accounting**, certified fiscal invoicing, audited GAAP/IFRS financial statements, bank reconciliation or a substitute for an accountant. Revenue and costs are recognized by transaction date (cash basis). The Excel workbook is an independent tool and does **not** synchronize automatically with MySQL.

## Installation

1. Set up PHP 8.2+, `pdo_mysql`, MySQL 8+, HTTPS and a private MySQL user.
2. Copy `.env.example` to `.env`; set database credentials. In production use `SESSION_SECURE=1` and HTTPS.
3. Import `database/schema.sql` in MySQL.
4. Bootstrap an administrator using `php scripts/create-admin.php "Finance Admin" admin@example.com "a-long-unique-password"` (replace details; avoid shell password history).
5. Set web server document root to the `public/` directory only; deny web access to `.env`, `src`, `database`, `tests` and `scripts`.
6. Sign in, configure your company currency, create cash/bank accounts, post transactions, set budgets, review reports and close reconciled months.

Local testing (without HTTPS only): set `SESSION_SECURE=0` and run `php -S 127.0.0.1:8096 -t public`.

## Validation

```
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/domain_test.php
python tests/check_workbook.py
```

The workbook uses a 500-row ledger and six-column budget model; enter dates as ISO text `YYYY-MM-DD` and category/period strings consistently. Formula-based summaries remain linked to editable input cells. Account transfers managed in the web app are **not** automatically imported into the workbook.

**Security and operational limits:** Install one isolated database per company; role permissions are application-level. Require HTTPS, backups and restore tests, audit review and independent security assessment before live deployment. CSV exports may contain confidential finances; handle securely. There is no bank feed, FX valuation, VAT settlement, double-entry ledger, consolidation, external auditor approval or statutory report filing.

## Support and licensing

MIT License. The project is free to use and fork. Optional support: RedotPay user ID **1612817131**, Airtm **santosantonio.eng@gmail.com**, maintainer website https://santosantonio.it.ao/. See [SUPPORT.md](SUPPORT.md).
