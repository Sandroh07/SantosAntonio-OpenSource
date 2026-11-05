# Optional Excel VBA automation

The provided `.xlsx` workbook is macro-free and opens normally. To enable PDF export automation in **Microsoft Excel Desktop**:

1. Open `PetroCalc_Engineering_Dashboard.xlsx` and save a copy as **Excel Macro-Enabled Workbook (*.xlsm)**.
2. Press `Alt+F11`, select **File → Import File**, and choose `ExportDashboard.bas`.
3. From **Developer → Macros**, run `ExportPetroCalcDashboard` to export the `Dashboard` sheet as a timestamped PDF in the workbook folder.

This macro does not connect to the internet or receive payments. Review any downloaded VBA before enabling macros; use a trusted workbook location. The distributed `.xlsx` itself does not contain embedded VBA.
