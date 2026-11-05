# Release 03 — 2026-11-05

**Added projects 05–06:** `angola-payroll/` and `business-finance-360/`.

- Payroll: employer/employee social rates, 2026 Group A IRT bracket model, configurable allowance treatment, employee register, monthly draft/final runs, CSV, audit and roles. Not certified, no legal guarantee.
- Finance: account register, cash-based income/expense ledger, atomic transfers, monthly budgets, period locks, management reports, CSV, roles and audit.
- BusinessFinance Excel dashboard: five sheets, 1,223 spreadsheet formulas and a time-series chart; does not automatically sync with MySQL.
- Added syntax and domain tests to CI and workbook structural validation.
- Both apps include their own license, setup README and voluntary donation page.

**Verification performed:** PHP 8.4 lint for new apps; pure calculation tests; Excel workbook zip/XML/formula checks; local HTTP sign-in smoke. **Not performed:** MySQL end-to-end data flows, real payroll reconciliation, legal/regulatory certification, external security audit or production deployment.

---

# Release 02 — November 5, 2026

## Added

- ExcelFlow VBA: six-sheet cash flow workbook, budget tracking, 199 formulas, automatic annual/monthly summaries, dashboard with chart, voluntary donation info and optional VBA code.
- SmartInventory: PHP/MySQL inventory control with warehouse ledger, lot movements, transfers, admin/operator/viewer roles, CSV export and administrator bootstrap script.
- Consolidated four-project portfolio with a root GitHub Actions workflow, README, LICENSE and SUPPORT.md.

## Validated

- ExcelFlow: 6 sheets, 199 formulas, chart present, OOXML archive validates, no embedded macros, formula-error scan returned none.
- SmartInventory: PHP syntax check, domain quantity tests, web login (HTTP 200), anonymous dashboard redirect (HTTP 303).
- Release candidate applications 01 and 02 retain the validation statements from their own READMEs.

## Remaining before actual deployment

- Complete real MySQL deployment/integration tests for PHP apps; set production TLS, permissions, backup and monitoring.
- Open workbook in Excel Desktop and test `.bas` macros imported to `.xlsm`.
- Formal financial, tax and engineering review where required.
- Create or connect a suitable public GitHub portfolio repository, then push and confirm commit URLs.
