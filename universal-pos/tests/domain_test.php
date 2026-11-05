<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$cases = ['0'=>0,'12'=>1200,'12.5'=>1250,'12.50'=>1250,'000.03'=>3,'1000000.99'=>100000099];
foreach ($cases as $str=>$expected) { assert(moneyToMinor((string)$str)===$expected); if (moneyToMinor((string)$str)!==$expected) throw new RuntimeException('Money test failed'); }
foreach (['-1','1.234','1,2','1e3','abc','1.'] as $s) {
    try { moneyToMinor($s); throw new RuntimeException('Invalid amount accepted: '.$s); }
    catch (InvalidArgumentException) {}
}
if (saleTax(10000,1400)!==1400 || saleTax(1,5000)!==1 || saleTax(0,0)!==0) throw new RuntimeException('Tax test failed');
if (safeCsv('=SUM(A1:A2)') !== "'=SUM(A1:A2)") throw new RuntimeException('CSV test failed');
echo "PASS: amount parser, tax rounding, spreadsheet injection protection\n";
