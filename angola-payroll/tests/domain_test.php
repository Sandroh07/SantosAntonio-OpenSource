<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Payroll.php';
function eq(mixed $got,mixed $want,string $label): void {if($got!==$want){throw new RuntimeException("$label expected ".json_encode($want)." got ".json_encode($got));}}
$bands=[
 ['lower_bound'=>0,'upper_bound'=>150000,'fixed_amount'=>0,'rate'=>0],
 ['lower_bound'=>150000,'upper_bound'=>200000,'fixed_amount'=>12500,'rate'=>.16],
 ['lower_bound'=>200000,'upper_bound'=>300000,'fixed_amount'=>31250,'rate'=>.18],
 ['lower_bound'=>300000,'upper_bound'=>null,'fixed_amount'=>49250,'rate'=>.19],
];
eq(Payroll::tax(150000,$bands),0.0,'2026 exempt threshold');
eq(Payroll::tax(150001,$bands),12500.16,'2026 next bracket published fixed part');
eq(Payroll::tax(200000,$bands),20500.0,'2026 second bracket upper');
eq(Payroll::tax(200001,$bands),31250.18,'2026 third bracket lower');
$v=Payroll::calculate(['base_salary'=>200000,'taxable_allowance'=>20000,'exempt_allowance'=>10000,'social_base'=>200000],$bands,.03,.08);
eq($v['gross'],230000.0,'gross');eq($v['inss_employee'],6000.0,'worker INSS');eq($v['inss_employer'],16000.0,'employer INSS');
eq($v['taxable_base'],214000.0,'IRT base');eq($v['irt'],33770.0,'IRT');eq($v['net'],190230.0,'net');eq($v['employer_cost'],246000.0,'employer cost');
eq(Payroll::period('2026-11'),'2026-11','valid month');
foreach([['negative','-1'],['too-many-decimals','1.234'],['letters','nan']] as [$name,$s]){try{Payroll::money($s);throw new RuntimeException("Accepted $name");}catch(InvalidArgumentException){}}
try{Payroll::period('2026-13');throw new RuntimeException('Accepted bad month');}catch(InvalidArgumentException){}
echo "AngolaPayroll domain tests passed (2026 published bracket behaviour, INSS, totals, input validation).\n";
