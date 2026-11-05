<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';require_user();
$db=Database::connection();$month=Finance::month((string)($_GET['month']??''));$type=(string)($_GET['type']??'transactions');
$end=(new DateTimeImmutable($month.'-01'))->modify('+1 month')->format('Y-m-d');
if($type==='transactions'){$s=$db->prepare('SELECT t.entry_date,t.kind,a.code AS account,c.name AS category,t.reference_no,t.description,t.amount FROM transactions t JOIN accounts a ON a.id=t.account_id JOIN categories c ON c.id=t.category_id WHERE t.entry_date>=? AND t.entry_date<? ORDER BY t.entry_date,t.id');$s->execute([$month.'-01',$end]);}
elseif($type==='budget'){$s=$db->prepare("SELECT c.name AS category,COALESCE(b.planned_amount,0) AS budget,COALESCE((SELECT SUM(t.amount) FROM transactions t WHERE t.category_id=c.id AND t.kind='expense' AND t.entry_date>=? AND t.entry_date<?),0) AS actual FROM categories c LEFT JOIN budgets b ON b.category_id=c.id AND b.month=? WHERE c.kind='expense' ORDER BY c.name");$s->execute([$month.'-01',$end,$month]);}
else {http_response_code(400);exit('Invalid report.');}
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="finance-'.$type.'-'.$month.'.csv"');header('Cache-Control: no-store');
$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");
$first=$s->fetch();if($first){fputcsv($out,array_keys($first));$convert=static fn($val)=>is_string($val)&&preg_match('/^\s*[=+@\-]/u',$val)?"'".$val:$val;fputcsv($out,array_map($convert,array_values($first)));while($row=$s->fetch())fputcsv($out,array_map($convert,array_values($row)));}
fclose($out);
