<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
require_user();
$type=(string)($_GET['type']??'');
$db=Database::connection();
if($type==='balances') {
    $data=(new Inventory($db))->balances();
    $headers=['sku','name','unit','reorder_level','warehouse','warehouse_name','lot','balance','expiry_date'];
} elseif ($type==='ledger') {
    $data=$db->query('SELECT m.id,m.created_at,p.sku,w.code AS warehouse,m.kind,m.qty_change,m.lot,m.expiry_date,m.reference_code,m.notes,u.email AS operator_email,m.transfer_uuid FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN warehouses w ON w.id=m.warehouse_id JOIN users u ON u.id=m.performed_by ORDER BY m.id DESC LIMIT 100000')->fetchAll();
    $headers=['id','created_at','sku','warehouse','kind','qty_change','lot','expiry_date','reference_code','notes','operator_email','transfer_uuid'];
} else { http_response_code(404); exit('Unknown export.'); }
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="smartinventory-'.($type==='ledger'?'ledger':'balances').'-'.date('Ymd').'.csv"');
header('Cache-Control: private, no-store');
$out=fopen('php://output','w');
fputcsv($out,$headers);
foreach($data as $row)fputcsv($out,array_map(fn($h)=>csv_escape((string)($row[$h]??'')),$headers));
fclose($out);
function csv_escape(string $s): string {return preg_match('/^[\s\t]*[=+\-@]/u',$s)?"'".$s:$s;}
