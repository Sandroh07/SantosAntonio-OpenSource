<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';

function query_all(PDO $db,string $sql,array $params=[]): array { $s=$db->prepare($sql); $s->execute($params); return $s->fetchAll(); }
function query_one(PDO $db,string $sql,array $params=[]): array|false { $s=$db->prepare($sql); $s->execute($params); return $s->fetch(); }
function csv_guard(mixed $v): string {
    $s=(string)$v;
    if (preg_match('/^[\s\t]*[=+\-@]/u',$s)) { $s="'".$s; }
    return $s;
}
function option_rows(array $rows, string $label='name'): string {
    return implode('',array_map(fn($r)=>'<option value="'.(int)$r['id'].'">'.e($r[$label]).'</option>',$rows));
}
function card(string $label,string $value,string $hint=''): string {
    return '<div class="col-md-4"><div class="metric"><div class="muted">'.e($label).'</div><div class="big">'.e($value).'</div><div class="small text-secondary">'.e($hint).'</div></div></div>';
}
function render_title(string $title,string $desc=''): void {
    echo '<div class="mb-4"><h1>'.e($title).'</h1><p class="text-secondary">'.e($desc).'</p></div>';
}
$page=(string)($_GET['page']??'dashboard');
$allowed=['login','logout','dashboard','products','warehouses','movements','transfer','reports','users'];
if (!in_array($page,$allowed,true)) {http_response_code(404);$page='dashboard';}
$exception=null;
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verify_csrf();
        $action=(string)($_POST['action']??'');
        if($action==='login') {
            $db=Database::connection();
            if(!login($db,post_string('email',190),(string)($_POST['password']??''))) throw new DomainException('Invalid login or password.');
            redirect('dashboard','Welcome back.');
        }
        require_user();
        $db=Database::connection();
        if ($action==='logout'){ logout();header('Location: ?page=login',true,303);exit; }
        if ($action==='add_product') {
            require_role(['admin','operator']);
            $sku=post_string('sku',64); $name=post_string('name',200);$unit=post_string('unit',20);
            if(!$sku||!$name||!$unit)throw new InvalidArgumentException('SKU, name and unit are required.');
            $min=milli_to_qty(qty_to_milli(post_string('reorder_level')));
            $s=$db->prepare('INSERT INTO products(sku,name,unit,reorder_level) VALUES(?,?,?,?)');$s->execute([$sku,$name,$unit,$min]);
            redirect('products','Product created.');
        }
        if ($action==='add_warehouse') {
            require_role(['admin']);$code=post_string('code',40);$name=post_string('name',160);
            if (!$code||!$name)throw new InvalidArgumentException('Code and name are required.');
            $s=$db->prepare('INSERT INTO warehouses(code,name) VALUES(?,?)');$s->execute([$code,$name]);
            redirect('warehouses','Warehouse created.');
        }
        if ($action==='movement') {
            $u=require_role(['admin','operator']);
            (new Inventory($db))->move((int)($_POST['product_id']??0),(int)($_POST['warehouse_id']??0),post_string('quantity',30),post_string('kind',30),post_string('lot',80),valid_date(post_string('expiry_date',10)),post_string('reference_code',120),post_string('notes',300),(int)$u['id']);
            redirect('movements','Stock movement recorded.');
        }
        if ($action==='transfer') {
            $u=require_role(['admin','operator']);
            (new Inventory($db))->transfer((int)($_POST['product_id']??0),(int)($_POST['source_id']??0),(int)($_POST['target_id']??0),post_string('quantity',30),post_string('lot',80),post_string('reference_code',120),(int)$u['id']);
            redirect('transfer','Transfer completed atomically.');
        }
        if ($action==='add_user') {
            require_role(['admin']);
            $name=post_string('name',120);$email=post_string('email',190);$password=(string)($_POST['password']??'');$role=post_string('role',20);
            if (!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12||!in_array($role,['admin','operator','viewer'],true)) throw new InvalidArgumentException('Name, valid email, password (min 12 characters), and role are required.');
            $s=$db->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            redirect('users','User created.');
        }
        throw new InvalidArgumentException('Invalid operation.');
    }
    if($page==='logout'){require_user();logout();header('Location: ?page=login',true,303);exit;}
    if($page!=='login')require_user();
    if($page==='login'&&current_user())redirect('dashboard');
    if($page!=='login'){$db=Database::connection();}
} catch(Throwable $e){
    http_response_code($e instanceof PDOException?503:400);
    $exception=$e instanceof PDOException?'Database operation failed; check your configuration or entered values.':$e->getMessage();
    if($e instanceof PDOException)error_log('SmartInventory database error: '.$e->getMessage());
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartInventory · <?=e(ucfirst($page))?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/main.css">
</head><body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top"><div class="container-fluid px-lg-5">
<a class="navbar-brand fw-bold" href="?page=dashboard">◈ SmartInventory</a>
<?php if(current_user()): ?><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="nav"><ul class="navbar-nav me-auto">
<?php foreach(['dashboard'=>'Dashboard','products'=>'Products','warehouses'=>'Warehouses','movements'=>'Stock ledger','transfer'=>'Transfers','reports'=>'Reports'] as $key=>$label): ?><li class="nav-item"><a class="nav-link <?=$page===$key?'active':''?>" href="?page=<?=$key?>"><?=e($label)?></a></li><?php endforeach; ?>
<?php if(current_user()['role']==='admin'): ?><li class="nav-item"><a class="nav-link" href="?page=users">Users</a></li><?php endif; ?>
</ul><span class="navbar-text me-3"><?=e(current_user()['name'])?> · <?=e(current_user()['role'])?></span><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button class="btn btn-sm btn-outline-light">Sign out</button></form></div><?php endif; ?>
</div></nav><main class="container-fluid px-lg-5 py-4 main-wrap">
<?php if($exception): ?><div class="alert alert-danger" role="alert"><?=e($exception)?></div><?php endif; ?>
<?php if(!empty($_SESSION['flash'])): ?><div class="alert alert-success" role="status"><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif; ?>
<?php if($page==='login'): ?>
<section class="login-shell"><div class="panel p-4 p-md-5"><div class="eyebrow">BUSINESS OPERATIONS · OPEN SOURCE</div><h1 class="mt-2 mb-2">Your stock,<br>under control.</h1><p class="text-secondary mb-4">Sign in to manage products, warehouses, lots, and transactions.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="login"><label class="form-label">Email</label><input type="email" name="email" autocomplete="username" class="form-control mb-3" required><label class="form-label">Password</label><input type="password" name="password" autocomplete="current-password" class="form-control mb-4" required><button class="btn btn-primary w-100">Sign in</button></form>
<p class="small text-secondary mt-3 mb-0">An administrator creates accounts via the secured CLI or Users screen.</p></div></section>
<?php elseif(isset($db)&&$db instanceof PDO): ?>
<?php
$inventory=new Inventory($db);
$products=query_all($db,'SELECT * FROM products WHERE active=1 ORDER BY name');
$warehouses=query_all($db,'SELECT * FROM warehouses WHERE active=1 ORDER BY name');
$selectProducts=option_rows($products);$selectWarehouses=option_rows($warehouses);
$canEdit=in_array(current_user()['role'],['admin','operator'],true);
?>
<?php if($page==='dashboard'):
$stats=query_one($db,'SELECT (SELECT COUNT(*) FROM products WHERE active=1) AS products,(SELECT COUNT(*) FROM warehouses WHERE active=1) AS warehouses,(SELECT COUNT(*) FROM stock_movements) AS movements');
$balances=$inventory->balances();$low=array_filter($balances,fn($r)=>(float)$r['balance']<=(float)$r['reorder_level']);
?>
<?php render_title('Inventory overview','Warehouse visibility · lot-level tracking · stock safety'); ?>
<div class="row g-3 mb-4"><?=card('Active products',(string)$stats['products'],'Managed SKUs')?><?=card('Warehouses',(string)$stats['warehouses'],'Active locations')?><?=card('Ledger entries',(string)$stats['movements'],'Immutable audit history')?></div>
<div class="panel p-4"><div class="d-flex justify-content-between align-items-center"><h2>Reorder watch</h2><a class="btn btn-sm btn-outline-primary" href="?page=reports">View all balances</a></div>
<?php if(!$low): ?><p class="text-secondary mb-0">No positive-stock lots below threshold. Zero-stock SKUs are listed in the product register and should be checked there.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>SKU</th><th>Product</th><th>Warehouse</th><th>Lot</th><th>Balance</th><th>Reorder threshold</th></tr></thead><tbody><?php foreach(array_slice($low,0,20) as $r): ?><tr><td><?=e($r['sku'])?></td><td><?=e($r['name'])?></td><td><?=e($r['warehouse'])?></td><td><?=e($r['lot']?:'—')?></td><td class="text-danger"><?=e($r['balance'])?></td><td><?=e($r['reorder_level'])?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php elseif($page==='products'): ?>
<?php render_title('Product register','A single catalogue across all active warehouses.'); ?>
<div class="row g-4"><div class="col-lg-<?= $canEdit?'8':'12'?>"><div class="panel p-3"><div class="table-responsive"><table class="table"><thead><tr><th>SKU</th><th>Name</th><th>Unit</th><th>Reorder level</th></tr></thead><tbody><?php foreach($products as $r): ?><tr><td class="fw-bold"><?=e($r['sku'])?></td><td><?=e($r['name'])?></td><td><?=e($r['unit'])?></td><td><?=e($r['reorder_level'])?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
<?php if($canEdit): ?><div class="col-lg-4"><div class="panel p-4"><h2>Add product</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_product"><label class="form-label">SKU</label><input class="form-control mb-3" name="sku" maxlength="64" required><label class="form-label">Product name</label><input class="form-control mb-3" name="name" maxlength="200" required><label class="form-label">Unit (unit, kg, L, etc.)</label><input class="form-control mb-3" name="unit" value="unit" maxlength="20" required><label class="form-label">Reorder level</label><input class="form-control mb-3" name="reorder_level" value="0.000" inputmode="decimal" required><button class="btn btn-primary w-100">Save product</button></form></div></div><?php endif; ?></div>
<?php elseif($page==='warehouses'): ?>
<?php render_title('Warehouses','Set up independent locations for each business branch.'); ?>
<div class="row g-4"><div class="col-lg-8"><div class="panel p-3"><table class="table"><thead><tr><th>Code</th><th>Name</th></tr></thead><tbody><?php foreach($warehouses as $r): ?><tr><td class="fw-bold"><?=e($r['code'])?></td><td><?=e($r['name'])?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php if(current_user()['role']==='admin'): ?><div class="col-lg-4"><div class="panel p-4"><h2>Add warehouse</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_warehouse"><label class="form-label">Code</label><input name="code" class="form-control mb-3" maxlength="40" required><label class="form-label">Name</label><input name="name" class="form-control mb-3" maxlength="160" required><button class="btn btn-primary w-100">Save warehouse</button></form></div></div><?php endif; ?></div>
<?php elseif($page==='movements'): ?>
<?php render_title('Stock ledger','Record purchases, outgoing items, opening balance, and approved corrections.'); ?>
<?php if($canEdit): ?><div class="panel p-4 mb-4"><h2>New movement</h2><form method="post" class="row g-3"><?=csrf_field()?><input type="hidden" name="action" value="movement"><div class="col-md-3"><label class="form-label">Product</label><select class="form-select" name="product_id" required><?=$selectProducts?></select></div><div class="col-md-3"><label class="form-label">Warehouse</label><select class="form-select" name="warehouse_id" required><?=$selectWarehouses?></select></div><div class="col-md-3"><label class="form-label">Movement</label><select class="form-select" name="kind"><option value="receipt">Receipt (+)</option><option value="issue">Issue (-)</option><option value="opening">Opening (+)</option><option value="adjustment">Adjustment (+/-)</option></select></div><div class="col-md-3"><label class="form-label">Quantity</label><input name="quantity" class="form-control" value="1.000" required inputmode="decimal"></div><div class="col-md-3"><label class="form-label">Lot / batch</label><input name="lot" class="form-control" maxlength="80" placeholder="Optional"></div><div class="col-md-3"><label class="form-label">Expiry date</label><input name="expiry_date" type="date" class="form-control"></div><div class="col-md-3"><label class="form-label">Reference</label><input name="reference_code" class="form-control" maxlength="120"></div><div class="col-md-3"><label class="form-label">Notes</label><input name="notes" class="form-control" maxlength="300"></div><div class="col-12"><button class="btn btn-primary" <?=$products&&$warehouses?'':'disabled'?>>Record movement</button></div></form></div><?php endif; ?>
<div class="panel p-3"><h2>Latest transactions</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>SKU</th><th>Warehouse</th><th>Type</th><th>Qty ±</th><th>Lot</th><th>Reference</th><th>Operator</th></tr></thead><tbody><?php foreach(query_all($db,'SELECT m.*,p.sku,w.code,u.name AS operator_name FROM stock_movements m JOIN products p ON m.product_id=p.id JOIN warehouses w ON m.warehouse_id=w.id JOIN users u ON u.id=m.performed_by ORDER BY m.id DESC LIMIT 100') as $r): ?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['sku'])?></td><td><?=e($r['code'])?></td><td><?=e($r['kind'])?></td><td><?=e($r['qty_change'])?></td><td><?=e($r['lot'])?></td><td><?=e($r['reference_code'])?></td><td><?=e($r['operator_name'])?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php elseif($page==='transfer'): ?>
<?php render_title('Inter-warehouse transfer','Two corresponding ledger entries, committed together.'); ?>
<?php if($canEdit): ?><div class="panel p-4"><form method="post" class="row g-3"><?=csrf_field()?><input type="hidden" name="action" value="transfer"><div class="col-md-4"><label class="form-label">Product</label><select class="form-select" name="product_id" required><?=$selectProducts?></select></div><div class="col-md-4"><label class="form-label">From warehouse</label><select class="form-select" name="source_id" required><?=$selectWarehouses?></select></div><div class="col-md-4"><label class="form-label">To warehouse</label><select class="form-select" name="target_id" required><?=$selectWarehouses?></select></div><div class="col-md-4"><label class="form-label">Lot</label><input name="lot" class="form-control" maxlength="80" placeholder="Leave blank for unbatched"></div><div class="col-md-4"><label class="form-label">Quantity</label><input name="quantity" class="form-control" value="1.000" inputmode="decimal" required></div><div class="col-md-4"><label class="form-label">Reference</label><input name="reference_code" class="form-control" maxlength="120"></div><div class="col-12"><button class="btn btn-primary" <?=count($warehouses)>1&&$products?'':'disabled'?>>Commit transfer</button></div></form></div><?php else: ?><p class="text-secondary">Viewer access is read-only.</p><?php endif; ?>
<?php elseif($page==='reports'): ?>
<?php render_title('Reports and exports','Reconciled stock balances and downloadable CSV reports.'); $balances=$inventory->balances(); ?>
<div class="d-flex gap-2 mb-3"><a class="btn btn-primary" href="export.php?type=balances">Export balances CSV</a><a class="btn btn-outline-primary" href="export.php?type=ledger">Export ledger CSV</a></div>
<div class="panel p-3"><div class="table-responsive"><table class="table"><thead><tr><th>SKU</th><th>Product</th><th>Warehouse</th><th>Lot</th><th>Balance</th><th>Unit</th><th>Expiry</th></tr></thead><tbody><?php foreach($balances as $r): ?><tr><td><?=e($r['sku'])?></td><td><?=e($r['name'])?></td><td><?=e($r['warehouse'])?></td><td><?=e($r['lot']?:'—')?></td><td><?=e($r['balance'])?></td><td><?=e($r['unit'])?></td><td><?=e($r['expiry_date']?:'—')?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php elseif($page==='users'&&current_user()['role']==='admin'): ?>
<?php render_title('Team access','Create named staff accounts with the least privileges needed.'); ?>
<div class="row g-4"><div class="col-lg-7"><div class="panel p-3"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr></thead><tbody><?php foreach(query_all($db,'SELECT name,email,role,active FROM users ORDER BY id') as $r): ?><tr><td><?=e($r['name'])?></td><td><?=e($r['email'])?></td><td><?=e($r['role'])?></td><td><?=e($r['active']?'Yes':'No')?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="col-lg-5"><div class="panel p-4"><h2>Create user</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add_user"><label class="form-label">Name</label><input class="form-control mb-3" name="name" maxlength="120" required><label class="form-label">Email</label><input type="email" class="form-control mb-3" name="email" maxlength="190" required><label class="form-label">Password (12+ chars)</label><input type="password" class="form-control mb-3" name="password" minlength="12" required><label class="form-label">Role</label><select class="form-select mb-3" name="role"><option value="viewer">Viewer</option><option value="operator">Operator</option><option value="admin">Admin</option></select><button class="btn btn-primary">Create account</button></form></div></div></div>
<?php else: ?><div class="alert alert-warning">Page not available for your role.</div><?php endif; ?>
<?php endif; ?>
</main><footer class="footer text-center py-3">SmartInventory · Open source MIT · <a href="https://santosantonio.it.ao/" target="_blank" rel="noopener">Santos António</a> · Voluntary support: RedotPay 1612817131 · Airtm santosantonio.eng@gmail.com</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
