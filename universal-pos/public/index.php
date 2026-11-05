<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
function posted(string $key): string { return trim((string)($_POST[$key] ?? '')); }
function positiveId(string $key): int { $n = filter_var($_POST[$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if ($n === false) throw new InvalidArgumentException('Invalid identifier.'); return $n; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $action = posted('action');
        if ($action === 'login') {
            if (!loginUser(strtolower(posted('email')),posted('password'))) throw new RuntimeException('Incorrect email or password.');
            flash('Welcome back.','success'); go('dashboard');
        }
        if ($action === 'logout') { $_SESSION = []; session_regenerate_id(true); go('login'); }
        requireUser();
        if ($action === 'sale_create') {
            $decoded = json_decode(posted('cart_json'), true, 512, JSON_THROW_ON_ERROR);
            $saleId = createSale((int)user()['id'], [
                'items'=>$decoded,'discount'=>posted('discount'),'paid'=>posted('paid'),
                'payment_method'=>posted('payment_method'),'customer_id'=>posted('customer_id')
            ]);
            header('Location: ?p=receipt&id='.$saleId); exit;
        }
        if ($action === 'customer_save') {
            $name=posted('name'); $email=posted('email'); $phone=posted('phone');
            if ($name==='' || mb_strlen($name)>190) throw new InvalidArgumentException('Customer name required.');
            if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email.');
            run('INSERT INTO customers(name,email,phone) VALUES (?,?,?)',[$name,$email?:null,$phone?:null]);
            flash('Customer added.','success'); go('customers');
        }
        requireAdmin();
        if ($action === 'settings_save') {
            $name=posted('business_name'); $kind=posted('business_type'); $currency=strtoupper(posted('currency'));
            $tax=(float)posted('tax_rate');
            if ($name==='' || mb_strlen($name)>190 || !in_array($kind,['retail','restaurant','salon','pharmacy','auto','hardware','services','other'],true)) throw new InvalidArgumentException('Invalid business settings.');
            if (!preg_match('/^[A-Z]{3}$/',$currency)) throw new InvalidArgumentException('Use an ISO currency code (3 letters).');
            if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', posted('tax_rate')) || $tax<0 || $tax>100) throw new InvalidArgumentException('Tax rate must be 0–100%.');
            $basis=(int)round($tax*100);
            run('UPDATE settings SET business_name=?,business_type=?,currency=?,tax_rate_bp=?,stock_enabled=?,receipt_note=? WHERE id=1',
                [$name,$kind,$currency,$basis,isset($_POST['stock_enabled'])?1:0,posted('receipt_note')?:null]);
            flash('Business settings saved.','success'); go('settings');
        }
        if ($action === 'category_create') {
            $name=posted('name'); if ($name==='' || mb_strlen($name)>120) throw new InvalidArgumentException('Invalid category.');
            run('INSERT INTO categories(name) VALUES (?)',[$name]); flash('Category created.','success'); go('products');
        }
        if ($action === 'product_toggle') {
            $id=positiveId('id');
            run('UPDATE products SET active=1-active WHERE id=?',[$id]); flash('Product status updated.','success'); go('products');
        }
        if ($action === 'product_save') {
            $id = posted('id')===''?null:positiveId('id');
            $name=posted('name'); $sku=posted('sku'); $barcode=posted('barcode'); $type=posted('item_type');
            if ($name==='' || mb_strlen($name)>190 || !in_array($type,['product','service'],true)) throw new InvalidArgumentException('Invalid product.');
            $price=moneyToMinor(posted('price'));
            $qty=filter_var(posted('stock_qty'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>1_000_000]]);
            $threshold=filter_var(posted('low_stock_threshold'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>1_000_000]]);
            if ($qty === false || $threshold === false) throw new InvalidArgumentException('Invalid stock quantity.');
            $category=posted('category_id')===''?null:positiveId('category_id');
            $pdo=db();$pdo->beginTransaction();
            try {
                if ($id !== null) {
                    $current=one('SELECT stock_qty FROM products WHERE id=? FOR UPDATE',[$id]);
                    if (!$current) throw new InvalidArgumentException('Product does not exist.');
                    run('UPDATE products SET name=?,sku=?,barcode=?,item_type=?,price_cents=?,stock_qty=?,low_stock_threshold=?,category_id=? WHERE id=?',
                        [$name,$sku?:null,$barcode?:null,$type,$price,$qty,$threshold,$category,$id]);
                    $delta=$qty-(int)$current['stock_qty'];
                    if ($delta) run('INSERT INTO stock_movements(product_id,actor_user_id,delta,reason) VALUES (?,?,?,?)',[$id,user()['id'],$delta,'adjustment']);
                } else {
                    run('INSERT INTO products(name,sku,barcode,item_type,price_cents,stock_qty,low_stock_threshold,category_id) VALUES (?,?,?,?,?,?,?,?)',
                        [$name,$sku?:null,$barcode?:null,$type,$price,$qty,$threshold,$category]);
                    $id=(int)$pdo->lastInsertId();
                    if ($qty) run('INSERT INTO stock_movements(product_id,actor_user_id,delta,reason) VALUES (?,?,?,?)',[$id,user()['id'],$qty,'adjustment']);
                }
                $pdo->commit();
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
            flash('Item saved.','success'); go('products');
        }
        if ($action === 'user_create') {
            $email=strtolower(posted('email'));$name=posted('name');$pass=posted('password');$role=posted('role');
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $name==='' || mb_strlen($name)>120 || strlen($pass)<12 || !in_array($role,['admin','cashier'],true)) throw new InvalidArgumentException('Invalid user; password needs at least 12 characters.');
            run('INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?)',[$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
            flash('Team member added.','success'); go('users');
        }
        throw new InvalidArgumentException('Unsupported request.');
    } catch (Throwable $e) {
        error_log('[UniversalPOS] '.$e->getMessage());
        $safe=$e instanceof PDOException ? 'Database operation failed. Verify unique fields and connectivity.' : $e->getMessage();
        flash($safe,'danger'); go(in_array($_POST['action']??'',['sale_create'],true)?'pos':(in_array($_POST['action']??'',['login'],true)?'login':(in_array($_POST['action']??'',['product_save','product_toggle','category_create'],true)?'products':(in_array($_POST['action']??'',['settings_save'],true)?'settings':(in_array($_POST['action']??'',['user_create'],true)?'users':'customers')))));
    }
}
$page=(string)($_GET['p'] ?? (user()?'dashboard':'login'));
if ($page === 'login') {
    if (user()) go('dashboard');
    pageTop('Sign in','login');
    echo '<div class="row justify-content-center"><div class="col-md-5"><div class="card p-4 p-lg-5"><div class="mb-3"><span class="badge soft">Universal business management</span></div><h1 class="h3">Sign in to your workspace</h1><p class="text-secondary">Use the account created by your administrator.</p><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="login"><label class="form-label">Email</label><input required type="email" name="email" class="form-control mb-3" autocomplete="username"><label class="form-label">Password</label><input required type="password" name="password" class="form-control mb-3" autocomplete="current-password"><button class="btn btn-primary w-100">Sign in</button></form></div></div></div>';
    pageBottom(); exit;
}
requireUser();
if ($page === 'dashboard') {
    $count=one('SELECT COUNT(*) n FROM products WHERE active=1')['n']??0;
    $today=one('SELECT COUNT(*) n,COALESCE(SUM(total_cents),0) total FROM sales WHERE created_at>=CURDATE() AND created_at<CURDATE()+INTERVAL 1 DAY');
    $week=one('SELECT COUNT(*) n,COALESCE(SUM(total_cents),0) total FROM sales WHERE created_at>=CURDATE()-INTERVAL 6 DAY');
    $low=all('SELECT name,stock_qty,low_stock_threshold FROM products WHERE active=1 AND item_type="product" AND stock_qty<=low_stock_threshold ORDER BY stock_qty ASC LIMIT 8');
    $recent=all('SELECT s.id,s.sale_no,s.total_cents,s.created_at,u.name cashier FROM sales s JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 8');
    $daily=all('SELECT DATE(created_at) day,SUM(total_cents) total FROM sales WHERE created_at>=CURDATE()-INTERVAL 6 DAY GROUP BY DATE(created_at) ORDER BY day');
    $max=max(1, ...array_map(fn($x)=>(int)$x['total'],$daily));
    pageTop('Dashboard','dashboard');
    echo '<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2"><div><h1 class="h2 fw-bold mb-1">Business overview</h1><p class="text-secondary mb-0">Sales, products and stock at a glance.</p></div><a class="btn btn-primary" href="?p=pos">+ New sale</a></div>';
    echo '<div class="row g-3 mb-4">';
    foreach([['Sales today',(int)$today['n']],['Revenue today',ccy((int)$today['total'])],['Last 7 days',ccy((int)$week['total'])],['Active items',$count]] as [$label,$value]) echo '<div class="col-6 col-xl-3"><div class="card p-3 p-lg-4"><div class="text-secondary small">'.h($label).'</div><div class="metric">'.h($value).'</div></div></div>';
    echo '</div><div class="row g-3"><div class="col-lg-7"><div class="card p-4"><h2 class="h5">Recent transactions</h2><div class="table-responsive"><table class="table"><thead><tr><th>Sale</th><th>Cashier</th><th>Date</th><th class="text-end">Total</th></tr></thead><tbody>';
    foreach($recent as $s) echo '<tr><td><a href="?p=receipt&amp;id='.(int)$s['id'].'">'.h($s['sale_no']).'</a></td><td>'.h($s['cashier']).'</td><td>'.h($s['created_at']).'</td><td class="text-end">'.ccy((int)$s['total_cents']).'</td></tr>';
    echo '</tbody></table></div></div></div><div class="col-lg-5"><div class="card p-4 mb-3"><h2 class="h5">Revenue by day</h2>';
    if (!$daily) echo '<p class="text-secondary">No sales yet.</p>';
    foreach($daily as $d) echo '<div class="small mb-2">'.h($d['day']).' <span class="float-end">'.ccy((int)$d['total']).'</span><div class="progress mt-1" style="height:10px"><div class="progress-bar bg-info" style="width:'.round((int)$d['total']/$max*100).'%"></div></div></div>';
    echo '</div><div class="card p-4"><h2 class="h5">Low stock</h2>';
    if (!$low) echo '<p class="text-secondary mb-0">No low-stock alerts.</p>';
    foreach($low as $x) echo '<div class="d-flex justify-content-between border-bottom py-2"><span>'.h($x['name']).'</span><b>'.(int)$x['stock_qty'].' left</b></div>';
    echo '</div></div></div>';
} elseif ($page === 'pos') {
    $items=all('SELECT id,name,sku,barcode,item_type,price_cents,stock_qty FROM products WHERE active=1 ORDER BY name LIMIT 2000');
    $customers=all('SELECT id,name FROM customers ORDER BY name LIMIT 2000');
    $tax=(int)(setting()['tax_rate_bp']??0)/100; $currency=setting()['currency']??'AOA';
    pageTop('New sale','pos');
    echo '<div class="mb-4"><h1 class="h2 fw-bold">Point of sale</h1><p class="text-secondary">Scan a barcode or search items. Final prices and stock are validated on the server.</p></div><div class="row g-4"><div class="col-lg-7"><input id="search" class="form-control form-control-lg mb-3" placeholder="Search name, SKU, barcode" autofocus><div id="catalog" class="row g-2"></div></div><div class="col-lg-5"><div class="card p-3 p-lg-4 sticky-lg-top" style="top:18px"><h2 class="h4">Current cart</h2><div id="cartRows" class="small mb-3"></div><hr><form id="checkoutForm" method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="sale_create"><input type="hidden" name="cart_json" id="cartJson"><input type="hidden" id="taxRate" value="'.h($tax).'"><input type="hidden" id="currency" value="'.h($currency).'"><div class="mb-2 d-flex justify-content-between">Subtotal <b id="subtotal">0</b></div><div class="mb-2"><label class="form-label">Discount ('.h($currency).')</label><input id="discount" name="discount" class="form-control" type="number" step="0.01" min="0" value="0"></div><div class="d-flex justify-content-between mb-2">Tax ('.h((string)$tax).'%) <b id="tax">0</b></div><div class="d-flex justify-content-between fs-4 fw-bold mb-3">Total <span id="grandTotal">0</span></div><label class="form-label">Customer (optional)</label><select name="customer_id" class="form-select mb-2"><option value="">Walk-in</option>';
    foreach($customers as $c) echo '<option value="'.(int)$c['id'].'">'.h($c['name']).'</option>';
    echo '</select><label class="form-label">Payment method</label><select name="payment_method" id="paymentMethod" class="form-select mb-2"><option value="cash">Cash</option><option value="card">Card (externally processed)</option><option value="transfer">Bank transfer (externally confirmed)</option><option value="mobile">Mobile payment (externally confirmed)</option></select><label class="form-label">Amount received</label><input name="paid" id="paid" type="number" step="0.01" min="0" class="form-control mb-2" value="0"><div class="small mb-3 text-secondary">Change: <b id="change">0</b></div><button id="complete" class="btn btn-primary btn-lg w-100" disabled>Complete sale</button></form></div></div></div>';
    $json=json_encode($items,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
    echo '<script type="application/json" id="catalogData">'.$json.'</script><script src="assets/pos.js" defer></script>';
} elseif ($page === 'products') {
    requireAdmin();
    $cats=all('SELECT id,name FROM categories ORDER BY name');
    $items=all('SELECT p.*,c.name category FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.name LIMIT 2000');
    $edit=(isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) ? one('SELECT * FROM products WHERE id=?',[(int)$_GET['edit']]):null;
    pageTop('Catalog','products');
    echo '<div class="row g-4"><div class="col-lg-8"><h1 class="h2 fw-bold">Product & service catalog</h1><p class="text-secondary">Products track stock; services do not.</p><div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>SKU</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach($items as $x) {
        echo '<tr><td><b>'.h($x['name']).'</b><div class="small text-secondary">'.h($x['category']??'').'</div></td><td>'.h($x['sku']??'').'</td><td>'.h($x['item_type']).'</td><td>'.ccy((int)$x['price_cents']).'</td><td>'.(int)$x['stock_qty'].'</td><td>'.((int)$x['active']?'Active':'Hidden').'</td><td><div class="d-flex gap-1"><a class="btn btn-outline-secondary btn-sm" href="?p=products&amp;edit='.(int)$x['id'].'">Edit</a><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="id" value="'.(int)$x['id'].'"><button class="btn btn-outline-secondary btn-sm" name="action" value="product_toggle">'.((int)$x['active']?'Hide':'Show').'</button></form></div></td></tr>';
    }
    echo '</tbody></table></div></div></div><div class="col-lg-4"><div class="card p-3 p-lg-4 mb-3"><h2 class="h5">'.($edit?'Edit item':'Add item').'</h2><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="'.h($edit['id']??'').'">';
    foreach([['name','Name','text',true],['sku','SKU','text',false],['barcode','Barcode','text',false]] as [$key,$label,$type,$required]) echo '<label class="form-label">'.h($label).'</label><input class="form-control mb-2" type="'.$type.'" name="'.$key.'" value="'.h($edit[$key]??'').'" '.($required?'required':'').'>';
    echo '<label class="form-label">Type</label><select class="form-select mb-2" name="item_type">';
    foreach(['product','service'] as $t) echo '<option value="'.$t.'" '.(($edit['item_type']??'product')===$t?'selected':'').'>'.ucfirst($t).'</option>';
    echo '</select><label class="form-label">Category</label><select name="category_id" class="form-select mb-2"><option value="">None</option>';
    foreach($cats as $c) echo '<option value="'.(int)$c['id'].'" '.(($edit['category_id']??null)==$c['id']?'selected':'').'>'.h($c['name']).'</option>';
    echo '</select>';
    foreach([['price','Price',isset($edit)?number_format((int)$edit['price_cents']/100,2,'.',''):'0.00','0.01'],['stock_qty','Stock quantity',$edit['stock_qty']??'0','1'],['low_stock_threshold','Low stock alert',$edit['low_stock_threshold']??'5','1']] as [$key,$label,$val,$step]) echo '<label class="form-label">'.h($label).'</label><input type="number" min="0" step="'.$step.'" class="form-control mb-2" name="'.$key.'" value="'.h($val).'" required>';
    echo '<button class="btn btn-primary w-100 mt-2">Save item</button></form></div><div class="card p-3"><h2 class="h5">Create category</h2><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="category_create"><input class="form-control mb-2" name="name" required placeholder="E.g. Food, Services"><button class="btn btn-outline-primary">Add category</button></form></div></div></div>';
} elseif ($page === 'sales') {
    $from=(string)($_GET['from']??date('Y-m-01'));$to=(string)($_GET['to']??date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) exit('Invalid date.');
    $list=all('SELECT s.id,s.sale_no,s.total_cents,s.payment_method,s.created_at,u.name cashier,c.name customer FROM sales s JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.created_at>=? AND s.created_at<DATE_ADD(?,INTERVAL 1 DAY) ORDER BY s.id DESC LIMIT 1000',[$from,$to]);
    if (isset($_GET['export'])) {
        header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="sales-'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');fputcsv($out,['Sale','Date','Cashier','Customer','Payment','Total minor units']);
        foreach($list as $s) fputcsv($out,array_map('safeCsv',[(string)$s['sale_no'],(string)$s['created_at'],(string)$s['cashier'],(string)($s['customer']??''),(string)$s['payment_method'],(string)$s['total_cents']]));
        exit;
    }
    pageTop('Sales','sales');
    echo '<h1 class="h2 fw-bold">Sales history</h1><div class="card p-3"><form method="get" class="row g-2 align-items-end mb-3"><input type="hidden" name="p" value="sales"><div class="col-sm-4"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="'.h($from).'"></div><div class="col-sm-4"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="'.h($to).'"></div><div class="col-sm-4"><button class="btn btn-primary">Apply filter</button> <a class="btn btn-outline-secondary" href="?p=sales&amp;from='.h($from).'&amp;to='.h($to).'&amp;export=1">CSV</a></div></form><p class="fw-bold">'.count($list).' sales · Total: '.ccy(array_sum(array_column($list,'total_cents'))).'</p><div class="table-responsive"><table class="table"><thead><tr><th>Sale</th><th>Date</th><th>Cashier</th><th>Customer</th><th>Payment</th><th>Total</th></tr></thead><tbody>';
    foreach($list as $s) echo '<tr><td><a href="?p=receipt&amp;id='.(int)$s['id'].'">'.h($s['sale_no']).'</a></td><td>'.h($s['created_at']).'</td><td>'.h($s['cashier']).'</td><td>'.h($s['customer']??'Walk-in').'</td><td>'.h($s['payment_method']).'</td><td>'.ccy((int)$s['total_cents']).'</td></tr>';
    echo '</tbody></table></div></div>';
} elseif ($page === 'customers') {
    $customers=all('SELECT * FROM customers ORDER BY id DESC LIMIT 500');pageTop('Customers','customers');
    echo '<div class="row g-4"><div class="col-lg-7"><h1 class="h2 fw-bold">Customers</h1><div class="card p-3"><table class="table"><tr><th>Name</th><th>Phone</th><th>Email</th></tr>';
    foreach($customers as $c) echo '<tr><td>'.h($c['name']).'</td><td>'.h($c['phone']??'').'</td><td>'.h($c['email']??'').'</td></tr>';
    echo '</table></div></div><div class="col-lg-5"><div class="card p-4"><h2 class="h5">New customer</h2><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="customer_save">';
    foreach([['name','Name'],['email','Email'],['phone','Phone']] as [$n,$label]) echo '<label class="form-label">'.h($label).'</label><input name="'.$n.'" class="form-control mb-2" '.($n==='name'?'required':'').'>';
    echo '<button class="btn btn-primary mt-2">Save customer</button></form></div></div></div>';
} elseif ($page === 'receipt') {
    $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if ($id===false) { http_response_code(400); exit('Invalid receipt'); }
    $sale=one('SELECT s.*,u.name cashier,c.name customer FROM sales s JOIN users u ON s.user_id=u.id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=?',[$id]);
    if (!$sale) { http_response_code(404); exit('Sale not found'); }
    $rows=all('SELECT item_name,qty,unit_price_cents,line_total_cents FROM sale_items WHERE sale_id=? ORDER BY id',[$id]);
    pageTop('Receipt');
    echo '<div class="receipt card p-4"><div class="text-center"><h1 class="h3">'.h(setting()['business_name']).'</h1><p class="text-secondary">Operational sale receipt — not a tax invoice</p></div><p><b>'.h($sale['sale_no']).'</b><br>'.h($sale['created_at']).'<br>Cashier: '.h($sale['cashier']).'<br>Customer: '.h($sale['customer']??'Walk-in').'</p><table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead><tbody>';
    foreach($rows as $x) echo '<tr><td>'.h($x['item_name']).'<div class="small text-secondary">'.ccy((int)$x['unit_price_cents']).' each</div></td><td>'.(int)$x['qty'].'</td><td>'.ccy((int)$x['line_total_cents']).'</td></tr>';
    echo '</tbody></table><div class="d-flex justify-content-between">Subtotal <b>'.ccy((int)$sale['subtotal_cents']).'</b></div><div class="d-flex justify-content-between">Discount <b>'.ccy((int)$sale['discount_cents']).'</b></div><div class="d-flex justify-content-between">Configured tax <b>'.ccy((int)$sale['tax_cents']).'</b></div><hr><div class="d-flex justify-content-between h4">Total <b>'.ccy((int)$sale['total_cents']).'</b></div><div class="d-flex justify-content-between">Paid <b>'.ccy((int)$sale['paid_cents']).'</b></div><div class="d-flex justify-content-between">Change <b>'.ccy((int)$sale['change_cents']).'</b></div><div class="text-center small text-secondary mt-3">'.h(setting()['receipt_note']??'Thank you for your business.').'</div><button onclick="window.print()" class="btn btn-primary mt-4 no-print">Print receipt</button><a href="?p=pos" class="btn btn-outline-secondary mt-2 no-print">New sale</a></div>';
} elseif ($page === 'users') {
    requireAdmin();$people=all('SELECT id,name,email,role,active,created_at FROM users ORDER BY id');pageTop('Team','users');
    echo '<h1 class="h2 fw-bold">Team access</h1><div class="row g-4"><div class="col-lg-7"><div class="card p-4"><table class="table"><tr><th>Name</th><th>Email</th><th>Role</th></tr>';
    foreach($people as $u) echo '<tr><td>'.h($u['name']).'</td><td>'.h($u['email']).'</td><td>'.h($u['role']).'</td></tr>';
    echo '</table></div></div><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Add employee</h2><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="user_create">';
    foreach([['name','Name','text'],['email','Email','email'],['password','Temporary password (12+ characters)','password']] as [$name,$label,$type]) echo '<label class="form-label">'.h($label).'</label><input required class="form-control mb-2" name="'.$name.'" type="'.$type.'">';
    echo '<label class="form-label">Role</label><select class="form-select mb-3" name="role"><option value="cashier">Cashier</option><option value="admin">Administrator</option></select><button class="btn btn-primary">Create account</button></form></div></div></div>';
} elseif ($page === 'settings') {
    requireAdmin();$s=setting();pageTop('Business setup','settings');
    echo '<h1 class="h2 fw-bold">Configure for your business</h1><p class="text-secondary">Retail, restaurants, salons, workshops and other services use the same core.</p><div class="card p-4 col-lg-7"><form method="post"><input type="hidden" name="csrf" value="'.h(token()).'"><input type="hidden" name="action" value="settings_save"><label class="form-label">Business name</label><input required class="form-control mb-3" name="business_name" value="'.h($s['business_name']).'"><label class="form-label">Business sector</label><select name="business_type" class="form-select mb-3">';
    foreach(['retail'=>'Retail / store','restaurant'=>'Restaurant / cafe','salon'=>'Salon / barber','pharmacy'=>'Pharmacy (commercial operations only)','auto'=>'Auto workshop','hardware'=>'Hardware shop','services'=>'Professional services','other'=>'Other'] as $key=>$label) echo '<option value="'.$key.'" '.($s['business_type']===$key?'selected':'').'>'.h($label).'</option>';
    echo '</select><label class="form-label">ISO currency code</label><input maxlength="3" required name="currency" class="form-control mb-3" value="'.h($s['currency']).'"><label class="form-label">Configured tax rate (%)</label><input type="number" step="0.01" min="0" max="100" required name="tax_rate" class="form-control mb-3" value="'.h(number_format((int)$s['tax_rate_bp']/100,2,'.','')).'"><div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="stockEnabled" name="stock_enabled" '.((int)$s['stock_enabled']?'checked':'').'><label for="stockEnabled" class="form-check-label">Enforce product stock levels</label></div><label class="form-label">Receipt footer note</label><textarea name="receipt_note" maxlength="500" class="form-control mb-3">'.h($s['receipt_note']??'').'</textarea><button class="btn btn-primary">Save configuration</button></form><div class="alert alert-warning mt-4 mb-0"><b>Compliance notice:</b> Tax settings are user-managed. This project is NOT AGT-certified and cannot replace legally required tax invoicing or e-invoicing software.</div></div>';
} else { http_response_code(404); pageTop('Not found'); echo '<h1>Page not found</h1>'; }
pageBottom();
