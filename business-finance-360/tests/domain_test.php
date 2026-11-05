<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Finance.php';
function eq(mixed $got,mixed $want,string $label): void {if($got!==$want)throw new RuntimeException("$label: ".json_encode($got)." != ".json_encode($want));}
$r=Finance::projectMonth([['kind'=>'income','amount'=>120000],['kind'=>'income','amount'=>5000.25],['kind'=>'expense','amount'=>10000.75],['kind'=>'expense','amount'=>250]],['50000','5000']);
eq($r['income'],125000.25,'income');eq($r['expenses'],10250.75,'expenses');eq($r['net'],114749.5,'net');eq($r['expense_budget'],55000.0,'budget');eq($r['budget_remaining'],44749.25,'remaining');
eq(Finance::money('500.99'),500.99,'amount');eq(Finance::month('2026-07'),'2026-07','month');eq(Finance::periodFromDate('2026-07-23'),'2026-07','date');
foreach(['-1','abc','1.234'] as $bad){try{Finance::money($bad);throw new RuntimeException('Unexpected amount accepted');}catch(InvalidArgumentException){}}
foreach(['2026-13-01','2026-02-30','2026-00-00'] as $bad){try{Finance::periodFromDate($bad);throw new RuntimeException('Unexpected date accepted');}catch(InvalidArgumentException){}}
echo "BusinessFinance 360 domain tests passed (monthly KPIs, money, dates).\n";
