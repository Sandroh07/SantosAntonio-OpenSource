# Santos António Open Source — Practical Software for Real Business Needs

**Six open-source application projects** · 20-project roadmap · MIT License · Voluntary donations only.

Santos António Open Source builds practical, independently usable software for retail businesses, finance departments, technical teams, small enterprises and energy professionals. The projects are especially informed by operational requirements common in Angola while aiming for international usability.

**Maintainer:** [Santos António](https://santosantonio.it.ao/) · [GitHub profile](https://github.com/Sandroh07) · [Email](mailto:santosantonio.eng@gmail.com)

## Published source projects

| # | Project | Technology | What the initial release includes | Validation and limitations |
|---|---|---|---|---|
| 01 | [UniversalPOS](universal-pos/) | PHP 8.2+, MySQL 8+, Bootstrap 5 | Adaptable product/service catalogue, checkout, stock, operational receipts, sales dashboard | Syntax + domain tests. MySQL integration and fiscal compliance review needed before live use. |
| 02 | [PetroCalc Engineering](petrocalc-engineering/) | HTML, JavaScript, Excel/VBA | API gravity, barrels/m³, gas volume reference conversions, GOR, pipe capacity, CSV, Excel dashboard | Engineering examples / mathematical tests; not certified measurement or safety calculations. |
| 03 | [ExcelFlow VBA](excel-flow-vba/) | Excel formulas, VBA source | Automated monthly cash flow, budget, 199 formulas, chart, six worksheet dashboard, optional macro import | Excel package + formula error checks passed; Excel Desktop macro execution untested. |
| 04 | [SmartInventory](smart-inventory/) | PHP 8.2+, MySQL 8+, Bootstrap 5 | Multiple warehouses, products, lots, controlled stock ledger, inter-warehouse transfers, roles, CSV | PHP syntax, pure domain tests and HTTP login smoke passed; live MySQL integration untested. |
| 05 | [AngolaPayroll](angola-payroll/) | PHP 8.2+, MySQL 8+, Bootstrap 5 | Staff directory, configurable Group A IRT/INSS parameters, payroll drafts and finalization, CSV, access control | Syntax + domain tests; specialist legal review and production MySQL integration essential. Not certified. |
| 06 | [BusinessFinance 360](business-finance-360/) | PHP 8.2+, MySQL 8+, Bootstrap 5, Excel | Account balances, income/expense ledger, transfers, budgets, month closing, CSV, 5-sheet Excel dashboard | Syntax + domain tests, workbook packaging/formulas verified; live MySQL and bank reconciliation untested. |

Each directory contains its own `README.md`, `LICENSE`, `SUPPORT.md`, code, tests and deployment notes. The tools are **not** automatically hosted by pushing source files to GitHub.

## Project ideas in the 20-project roadmap

07. FuelStation Manager — station transactions, pumps and reconciliation
08. GasLPG Manager — cylinder deposits, returns and distribution
09. FleetControl — maintenance, fuel and mileage
10. ConstructionBudget — estimates, materials and cost deviations
11. AgroBusiness ERP — crop costs, warehouse and farming operations
12. SchoolManager — school administrative operations
13. ClinicManager — booking and administration; no medical decision support
14. HotelDesk — property operations and reservations
15. ProcurementPro — vendors and bid comparisons
16. ExcelHR VBA — attendance, leave and personnel dashboards
17. OilWell Monitor — illustrative oil production KPIs
18. EnergyCost Analyzer — energy usage and savings reporting
19. LogisticsTrack — routing, delivery and dispatch management
20. Executive KPI Studio — customizable company-wide reporting

**Roadmap items #07–20 are not published software yet.** The release packages above should be evaluated and tested within each deployment environment. Documentation and compliance claims are intentionally limited to what has been checked.

## Contributing

Open a GitHub issue for a bug, improvement or question; see [CONTRIBUTING.md](CONTRIBUTING.md). Send focused pull requests with tests and avoid real customer data in screenshots, SQL exports and logs.

## Voluntary donations — every project

All six project directories contain `SUPPORT.md`. Donations are **100% voluntary**: access, modification and redistribution are never gated on payment.

- **RedotPay User ID:** `1612817131`
- **Airtm email:** `santosantonio.eng@gmail.com`
- **Website:** https://santosantonio.it.ao/

Confirm the recipient in your payment app before making a transfer. See [SUPPORT.md](SUPPORT.md).

## License and publication

All six released apps use the [MIT License](LICENSE). This is a public GitHub monorepository. Each project is independently installable. Publishing source files on GitHub does not deploy the applications or certify their operational or regulatory compliance.
