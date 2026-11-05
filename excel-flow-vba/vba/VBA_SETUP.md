# Optional VBA module for ExcelFlow

The `.xlsx` dashboard works **without macros**. For one-click recalculation, transaction input prompts and PDF export, import `ExcelFlow_Automation.bas` in **desktop Microsoft Excel**:

1. Open `spreadsheet/ExcelFlow_Cashflow_Dashboard.xlsx`.
2. Press `Alt+F11`, choose `File > Import File`, select `ExcelFlow_Automation.bas`.
3. Save the workbook as **Excel Macro-Enabled Workbook (`.xlsm`)**.
4. In the Excel macro dialog, run `RefreshDashboard`, `AddTransaction`, or `ExportDashboardPDF`.
5. Enable macros only if you understand and trust the source code. Review the module before importing.

VBA cannot run in Excel for the web or most non-Microsoft spreadsheet applications. The `.bas` module is source code, **not** an executable workbook and is not embedded in the `.xlsx` file.
