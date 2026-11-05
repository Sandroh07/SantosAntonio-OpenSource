"""Structural smoke test; uses only Python stdlib to inspect OOXML archive."""
from pathlib import Path
from zipfile import ZipFile
import xml.etree.ElementTree as ET

path=Path(__file__).resolve().parents[1]/'spreadsheet/ExcelFlow_Cashflow_Dashboard.xlsx'
assert path.is_file(),f'Missing workbook: {path}'
with ZipFile(path) as z:
    bad=z.testzip()
    assert bad is None, f'Corrupt archive entry: {bad}'
    names=z.namelist()
    assert 'xl/workbook.xml' in names
    assert any(('charts/chart' in x) and x.endswith('.xml') for x in names),'Dashboard chart missing'
    assert not any('vbaProject' in x for x in names),'Unsafe embedded VBA project in .xlsx'
    ns={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    xml=ET.fromstring(z.read('xl/workbook.xml'))
    found={e.attrib['name'] for e in xml.findall('.//m:sheet',ns)}
    expected={'Control','Transactions','Budget','Cash Flow','Dashboard','Guide'}
    assert found==expected, (found,expected)
    formulas=sum(z.read(n).count(b'<x:f') for n in names if n.startswith('xl/worksheets/sheet') and n.endswith('.xml'))
    assert formulas>120, f'Expected formula-driven workbook, only {formulas} formulas found'
    print(f'Workbook OK: {len(found)} sheets, {formulas} formulas, chart present; macros provided separately.')
