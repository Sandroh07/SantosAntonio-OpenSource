Attribute VB_Name = "ExcelFlow_Automation"
Option Explicit

' Santos António Open Source / ExcelFlow VBA.
' Import this file into Excel Desktop VBA, then Save As *.xlsm.
' No automatic code is executed on workbook open.

Public Sub RefreshDashboard()
    Application.CalculateFull
    ThisWorkbook.Worksheets("Dashboard").Activate
    MsgBox "ExcelFlow dashboard recalculated.", vbInformation, "ExcelFlow"
End Sub

Public Sub ExportDashboardPDF()
    Dim ws As Worksheet
    Dim outputPath As String
    If Len(ThisWorkbook.Path) = 0 Then
        MsgBox "Save the workbook before exporting.", vbExclamation, "ExcelFlow"
        Exit Sub
    End If
    Set ws = ThisWorkbook.Worksheets("Dashboard")
    outputPath = ThisWorkbook.Path & Application.PathSeparator & _
        "ExcelFlow_Dashboard_" & Format(Date, "yyyymmdd") & ".pdf"
    Application.CalculateFull
    ws.PageSetup.PrintArea = "$A$1:$L$28"
    ws.PageSetup.Orientation = xlLandscape
    ws.PageSetup.FitToPagesWide = 1
    ws.PageSetup.Zoom = False
    ws.ExportAsFixedFormat Type:=xlTypePDF, Filename:=outputPath, Quality:=xlQualityStandard
    MsgBox "PDF saved to: " & outputPath, vbInformation, "ExcelFlow"
End Sub

Public Sub AddTransaction()
    Dim ws As Worksheet
    Dim rowNum As Long
    Dim dateText As String, txnType As String, category As String
    Dim description As String, amountText As String, amountValue As Double
    Dim vDate As Date
    Set ws = ThisWorkbook.Worksheets("Transactions")
    dateText = Trim$(InputBox("Transaction date YYYY-MM-DD", "ExcelFlow", Format(Date, "yyyy-mm-dd")))
    If Len(dateText) = 0 Then Exit Sub
    On Error GoTo DateError
    If Len(dateText) <> 10 Or Mid$(dateText, 5, 1) <> "-" Or Mid$(dateText, 8, 1) <> "-" Then GoTo DateError
    vDate = DateSerial(CInt(Left$(dateText, 4)), CInt(Mid$(dateText, 6, 2)), CInt(Right$(dateText, 2)))
    If Format$(vDate, "yyyy-mm-dd") <> dateText Then GoTo DateError
    On Error GoTo 0
    txnType = Trim$(InputBox("Type: Inflow or Outflow", "ExcelFlow", "Inflow"))
    If txnType <> "Inflow" And txnType <> "Outflow" Then
        MsgBox "Type must be Inflow or Outflow.", vbExclamation
        Exit Sub
    End If
    category = Trim$(InputBox("Category (must match Budget)", "ExcelFlow", "Sales"))
    If Len(category) = 0 Then Exit Sub
    description = Trim$(InputBox("Description", "ExcelFlow", "New transaction"))
    amountText = Trim$(InputBox("Positive amount", "ExcelFlow", "1000"))
    If Len(amountText) = 0 Or Not IsNumeric(amountText) Then
        MsgBox "Invalid amount.", vbExclamation
        Exit Sub
    End If
    amountValue = CDbl(amountText)
    If amountValue <= 0 Then
        MsgBox "Amount must be greater than zero.", vbExclamation
        Exit Sub
    End If
    For rowNum = 5 To 504
        If Len(Trim$(CStr(ws.Cells(rowNum, 1).Value))) = 0 Then Exit For
    Next rowNum
    If rowNum > 504 Then
        MsgBox "Transaction table is full (500 entries).", vbExclamation
        Exit Sub
    End If
    ws.Cells(rowNum, 1).Value = vDate
    ws.Cells(rowNum, 2).Value = txnType
    ws.Cells(rowNum, 3).Value = category
    ws.Cells(rowNum, 4).Value = description
    ws.Cells(rowNum, 6).Value = amountValue
    Application.CalculateFull
    MsgBox "Transaction stored in row " & rowNum & ".", vbInformation, "ExcelFlow"
    Exit Sub
DateError:
    MsgBox "Invalid date. Use YYYY-MM-DD.", vbExclamation, "ExcelFlow"
End Sub
