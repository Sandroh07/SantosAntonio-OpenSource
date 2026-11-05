#!/usr/bin/env python3
"""Lightweight stdlib-only integrity test of generated XLSX formulas and named sheets."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as ET

file = Path(__file__).resolve().parents[1] / "spreadsheet" / "BusinessFinance_360_Dashboard.xlsx"
ns = {"s": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
with ZipFile(file) as archive:
    names=set(archive.namelist())
    assert "xl/workbook.xml" in names and "xl/worksheets/sheet1.xml" in names
    wb=ET.fromstring(archive.read("xl/workbook.xml"))
    sheets=[x.attrib["name"] for x in wb.findall("s:sheets/s:sheet",ns)]
    assert sheets == ["Dashboard","Ledger","Budgets","Accounts","Guide & Sources"],sheets
    formula_count=sum(len(ET.fromstring(archive.read(name)).findall('.//s:f',ns)) for name in names if name.startswith('xl/worksheets/sheet') and name.endswith('.xml'))
    assert formula_count >= 600,formula_count
print(f"BusinessFinance workbook valid: {len(sheets)} sheets; {formula_count} formulas")
