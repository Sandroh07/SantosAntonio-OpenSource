# ExcelFlow VBA — Open-Source Cash Flow and Budget Dashboard

A practical **Excel financial management workbook** for small business owners, financial analysts and accountants. The main spreadsheet is formula-driven, no paid service or macros required. Optional VBA macros add one-click tasks in desktop Microsoft Excel.

## Included

- `spreadsheet/ExcelFlow_Cashflow_Dashboard.xlsx` — six sheets: Control, Transactions, Budget, Cash Flow, Dashboard and Guide.
- `vba/ExcelFlow_Automation.bas` — optional VBA source for transaction prompt, recalculation and PDF export.
- `vba/VBA_SETUP.md` — Excel Desktop installation instructions.
- `tests/check_workbook.py` — structural and formula-oriented verification with standard library.
- `LICENSE` (MIT) and `SUPPORT.md` for strictly voluntary donations.

## How to use

1. Open the `.xlsx` file in Excel or a compatible spreadsheet viewer.
2. Change the **yellow** cells in `Control` to set the financial year, opening cash, currency and company name.
3. Replace **fictional demonstration transactions** in `Transactions` rows 5–14 with your own records. Enter up to 500 transactions in rows 5–504.
4. Enter positive transaction amounts; choose `Inflow` or `Outflow` in the type column. Dates must be true Excel dates.
5. Adjust **yellow** monthly expense and revenue targets in `Budget`.
6. Review automatically calculated monthly and annual actual inflows, outflows, net position, budget variance and cumulative cash in `Cash Flow`, then open `Dashboard`.
7. Import the optional VBA module if you want transaction prompts or one-click PDF exports (see `vba/VBA_SETUP.md`).

### Important finance assumptions

- This workbook is a **cash basis management model**, not accrual accounting or an audited financial statement.
- Sample entries and budgets are fictional and **not real company data**.
- Categories are fixed to the eleven rows in the provided budget and drop-down. Change both category labels and transaction records consistently if adapting.
- Budget is not indexed to inflation, VAT, local tax law, or FX automatically; values are manual assumptions.
- Maximum 500 transactions; expand transaction ranges in formulas if you need more.
- Default currency **AOA**, but can be changed. The workbook does not fetch live exchange rates.
- VBA support is optional, distributed as a source `.bas` file, and does not run in online Excel.

## Testing

Run `python tests/check_workbook.py` to check XLSX packaging, required sheets, formulas, macro exclusion, and chart XML. This is a **structural test** and does not replace verifying calculations in Excel Desktop. No VBA execution was performed in the project-building environment.

## Free and open source

MIT License. Pull requests are welcome. Donations are voluntary and never required to use the spreadsheet.

**RedotPay:** `1612817131` · **Airtm:** `santosantonio.eng@gmail.com` · **Website:** https://santosantonio.it.ao/

See [SUPPORT.md](SUPPORT.md).
