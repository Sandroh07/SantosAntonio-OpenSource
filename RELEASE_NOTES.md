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
