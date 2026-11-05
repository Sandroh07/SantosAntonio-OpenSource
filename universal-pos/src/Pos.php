<?php
declare(strict_types=1);
/** Server-authoritative sale creation. Prices and stock are never trusted from the browser. */
function createSale(int $actor, array $input): int {
    $items = $input['items'] ?? null;
    if (!is_array($items) || !$items || count($items) > 100) throw new InvalidArgumentException('Add between 1 and 100 items.');
    $quantities = [];
    foreach ($items as $item) {
        if (!is_array($item) || !isset($item['id'], $item['qty'])) throw new InvalidArgumentException('Invalid cart item.');
        $id = filter_var($item['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $qty = filter_var($item['qty'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>9999]]);
        if ($id === false || $qty === false) throw new InvalidArgumentException('Invalid cart quantity.');
        $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
        if ($quantities[$id] > 9999) throw new InvalidArgumentException('Too many units of one item.');
    }
    $discount = moneyToMinor((string)($input['discount'] ?? '0'));
    $paid = moneyToMinor((string)($input['paid'] ?? '0'));
    $method = (string)($input['payment_method'] ?? 'cash');
    if (!in_array($method, ['cash','card','transfer','mobile'], true)) throw new InvalidArgumentException('Unsupported payment method.');
    $customer = empty($input['customer_id']) ? null : filter_var($input['customer_id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($customer === false) throw new InvalidArgumentException('Invalid customer.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $config = one('SELECT tax_rate_bp,stock_enabled FROM settings WHERE id=1 FOR UPDATE');
        if (!$config) throw new RuntimeException('Business settings are missing.');
        $rows=[];$subtotal=0;
        ksort($quantities);
        foreach ($quantities as $id => $qty) {
            $p = one('SELECT id,name,price_cents,stock_qty,item_type,active FROM products WHERE id=? FOR UPDATE', [$id]);
            if (!$p || !(int)$p['active']) throw new InvalidArgumentException('An item is no longer available.');
            if ((int)$config['stock_enabled'] && $p['item_type'] === 'product' && (int)$p['stock_qty'] < $qty) {
                throw new InvalidArgumentException('Insufficient stock for: ' . $p['name']);
            }
            $line = (int)$p['price_cents'] * $qty;
            if ($line > 1_000_000_000_000 || $subtotal + $line > 1_000_000_000_000) throw new InvalidArgumentException('Sale exceeds limit.');
            $subtotal += $line;
            $rows[] = [$p,$qty,$line];
        }
        if ($discount > $subtotal) throw new InvalidArgumentException('Discount exceeds subtotal.');
        $tax = saleTax($subtotal - $discount, (int)$config['tax_rate_bp']);
        $total = $subtotal - $discount + $tax;
        if ($method !== 'cash') $paid = $total;
        if ($paid < $total) throw new InvalidArgumentException('Amount paid is below total.');
        if ($paid > 1_000_000_000_000) throw new InvalidArgumentException('Payment exceeds limit.');
        if ($customer !== null && !one('SELECT id FROM customers WHERE id=?',[$customer])) throw new InvalidArgumentException('Customer not found.');
        run('INSERT INTO sales(user_id,customer_id,subtotal_cents,discount_cents,tax_cents,total_cents,paid_cents,change_cents,payment_method) VALUES (?,?,?,?,?,?,?,?,?)',
            [$actor,$customer,$subtotal,$discount,$tax,$total,$paid,$paid-$total,$method]);
        $saleId = (int)$pdo->lastInsertId();
        run('UPDATE sales SET sale_no=? WHERE id=?', ['POS-'.date('Ymd').'-'.str_pad((string)$saleId,6,'0',STR_PAD_LEFT),$saleId]);
        foreach ($rows as [$p,$qty,$line]) {
            run('INSERT INTO sale_items(sale_id,product_id,item_name,qty,unit_price_cents,line_total_cents) VALUES (?,?,?,?,?,?)',
                [$saleId,$p['id'],$p['name'],$qty,$p['price_cents'],$line]);
            if ($config['stock_enabled'] && $p['item_type']==='product') {
                run('UPDATE products SET stock_qty=stock_qty-? WHERE id=?',[$qty,$p['id']]);
                run('INSERT INTO stock_movements(product_id,sale_id,actor_user_id,delta,reason) VALUES (?,?,?,? ,?)',[$p['id'],$saleId,$actor,-$qty,'sale']);
            }
        }
        $pdo->commit(); return $saleId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
