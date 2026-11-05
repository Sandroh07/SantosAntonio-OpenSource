<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
require_user();
$db=Database::connection();
$id=filter_input(INPUT_GET,'run',FILTER_VALIDATE_INT);
if(!$id||$id<1){http_response_code(400);exit('Invalid run');}
$s=$db->prepare('SELECT period FROM payroll_runs WHERE id=?');$s->execute([$id]);$run=$s->fetch();
if(!$run){http_response_code(404);exit('Run not found');}
$s=$db->prepare('SELECT staff_code,employee_name,base_salary,taxable_allowance,exempt_allowance,social_base,gross,taxable_base,inss_employee,inss_employer,irt,net,employer_cost FROM payroll_lines WHERE run_id=? ORDER BY employee_name');$s->execute([$id]);
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="payroll-'.str_replace('-','',(string)$run['period']).'.csv"');
header('Cache-Control: no-store');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['staff_code','employee_name','base_salary','taxable_allowance','exempt_allowance','social_base','gross','taxable_base','inss_employee','inss_employer','irt','net','employer_cost']);
while($row=$s->fetch()){$row['employee_name']=preg_match('/^\s*[=+@\-]/u',$row['employee_name'])?"'".$row['employee_name']:$row['employee_name'];fputcsv($out,array_values($row));}
fclose($out);
