Attribute VB_Name = "PetroCalcExport"
Option Explicit

' Optional VBA module for Excel Desktop. Import this .bas file, then save as .xlsm.
' Export is always initiated by the user. Nothing is sent over the network.
Public Sub ExportPetroCalcDashboard()
    Dim outPath As String
    If Len(ThisWorkbook.Path) = 0 Then
        MsgBox "Save your workbook before exporting the dashboard.", vbExclamation
        Exit Sub
    End If
    outPath = ThisWorkbook.Path & Application.PathSeparator & "PetroCalc_Report_" & Format(Now, "yyyymmdd_hhnnss") & ".pdf"
    ThisWorkbook.Worksheets("Dashboard").ExportAsFixedFormat _
        Type:=xlTypePDF, Filename:=outPath, Quality:=xlQualityStandard, _
        IncludeDocProperties:=True, IgnorePrintAreas:=False, OpenAfterPublish:=False
    MsgBox "Dashboard exported to: " & outPath, vbInformation
End Sub
