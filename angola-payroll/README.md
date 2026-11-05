# AngolaPayroll — Open-Source Payroll Management (Group A)

A PHP 8.2+ / MySQL 8+ / Bootstrap 5 application for **illustrative Angolan payroll calculation** with employee records, configurable contributions, an IRT bracket table, drafts, finalization, role-based access and CSV exports.

> **Not a certified payroll, statutory filing, or tax advisory system.** The tax configuration must be reviewed and approved by an appropriately qualified Angolan payroll/tax specialist before any real payroll processing. The application is not affiliated with AGT, INSS or the Government of Angola.

## Features

- Employees: staff codes, department, monthly base pay, separately identified taxable and exempt allowances, and an optional override for social-security assessment base.
- Configurable worker / employer social contributions (initially 3% / 8% for the standard employee arrangement).
- **2026 Group A IRT** fixed-amount + marginal-rate brackets preloaded from the published annex of Law No. 14/25; do not assume they apply to every worker or income category.
- Administrative rule-verification flag; cannot generate payroll until an administrator marks the rule configuration as reviewed.
- Monthly draft payroll generation, net salaries, employee IRT / contributions, employer cost, review and irreversible finalization at the application level.
- CSV export, user roles (admin, payroll, viewer), prepared PDO queries, CSRF tokens, secure password hashes and audit trail.
- English interface and MIT open-source license.

## Calculation scope

For the current configuration, the algorithm uses:

```
Gross = base salary + taxable allowance + exempt allowance
Worker INSS = INSS assessment base * worker rate
Employer INSS = INSS assessment base * employer rate
IRT assessment = max(0, base salary + taxable allowance - worker INSS)
IRT = fixed amount of bracket + (assessment - bracket lower bound) * bracket marginal rate
Net = gross - worker INSS - IRT
Employer cost = gross + employer INSS
```

The **default INSS assessment base is the employee's base salary**, unless explicitly overridden for that employee. This is an *illustrative configuration*, not a legal conclusion about the treatment of specific allowances, benefits, workers or social-security regimes. Exempt allowance status must also be independently reviewed. Exceptional regimes, leave, annual bonuses, dismissal indemnities, loans, garnishments, retroactive adjustments and statutory export formats are outside this release.

The published 2026 bracket data contains fixed amounts that produce jumps at some thresholds. Do not smooth the schedule or infer a different table without reviewing official materials and professional advice. The admin can update fixed amounts/rates for existing brackets; to add a new year, import a correctly approved set of year-specific ranges into `tax_brackets` via database migration before approving it. **Never copy 2026 bands blindly to another tax year.**

## Requirements and installation

1. Provision PHP 8.2+, `pdo_mysql`, MySQL 8+, HTTPS and a private database account.
2. Copy `.env.example` to `.env`; set DB_HOST, DB_NAME, DB_USER, DB_PASSWORD and `SESSION_SECURE=1` under HTTPS.
3. Import `database/schema.sql` into MySQL. This creates the dedicated database, tables and the initial 2026 tax configuration.
4. Create a first admin via CLI: `php scripts/create-admin.php "Company Admin" admin@example.com "a-strong-unique-password"` (replace details). Avoid storing passwords in shell history.
5. Point the web-server document root **only** to the `public/` directory; never expose `.env`, `src/`, `database/` or `scripts/`.
6. Log in, add employees, independently verify rates and earning types with a specialist, mark the rules reviewed, generate draft payroll, export CSV and finalize only after reconciliation.

For local development without HTTPS: set `SESSION_SECURE=0` in `.env` and run `php -S 127.0.0.1:8095 -t public`. Restore secure cookie settings in production.

## Validation

```
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/domain_test.php
```

The domain tests cover published 2026 bracket arithmetic, social contributions, net pay, employer cost, input validation and period validation. They do **not** prove compliance or full database integration. Live MySQL processing, privacy controls, backups, restoration, penetration testing and regulatory review are required before use with real employee data.

## Sources and constraints

- Angolan State Budget Law No. 14/25, Annex I, IRT 2026: https://www.angolex.com/paginas/leis/lei-que-aprova-o-orcamento-geral-do-estado-para-o-exercicio-economico-de-2026a-14a-25a.html
- AGT portal — Income from Employment (IRT): https://portaldocontribuinte.minfin.gov.ao/impostos-e-taxas/imposto-sobre-rendimentos-do-trabalho
- Presidential Decree No. 227/18 — general 8% employer / 3% employee contributions, Art. 12: https://lex.ao/docs/presidente-da-republica/2018/decreto-presidencial-n-o-227-18-de-27-de-setembro/

**These sources require current verification for each deployment; this software is not an official interpretation.**

## Privacy and security

Payroll data is personally sensitive. Deploy with HTTPS, restricted administrative access, encrypted backups, minimum permissions, a privacy notice, retention policy, secure hosting and a lawful processing basis. Do not commit `.env`, real staff data, generated CSV exports or database dumps. Users must not have direct access to files outside `public/`. The release supports one company per isolated installation, not multi-tenant SaaS.

## Community, license & donations

MIT License. Contributions are welcome. The software is free and **donations are voluntary**: RedotPay `1612817131`; Airtm `santosantonio.eng@gmail.com`; https://santosantonio.it.ao/. See [SUPPORT.md](SUPPORT.md).
