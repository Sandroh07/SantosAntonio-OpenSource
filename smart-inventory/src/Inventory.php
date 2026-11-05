<?php
declare(strict_types=1);
final class Inventory {
    public function __construct(private readonly PDO $db) {}

    public function available(int $product, int $warehouse, string $lot=''): int {
        $s=$this->db->prepare('SELECT COALESCE(SUM(qty_change),0) AS available FROM stock_movements WHERE product_id=? AND warehouse_id=? AND lot=?');
        $s->execute([$product,$warehouse,$lot]);
        return qty_to_milli((string)$s->fetchColumn(),true);
    }
    private function lockProduct(int $id): void {
        $s=$this->db->prepare('SELECT id FROM products WHERE id=? AND active=1 FOR UPDATE');
        $s->execute([$id]); if (!$s->fetchColumn()) throw new DomainException('Product missing or inactive.');
    }
    private function checkWarehouse(int $id): void {
        $s=$this->db->prepare('SELECT id FROM warehouses WHERE id=? AND active=1');
        $s->execute([$id]); if (!$s->fetchColumn()) throw new DomainException('Warehouse missing or inactive.');
    }
    private function record(int $product,int $warehouse,int $qty,string $kind,string $lot,?string $expiry,string $reference,string $notes,int $actor,?string $transferId=null): void {
        $s=$this->db->prepare('INSERT INTO stock_movements(product_id,warehouse_id,qty_change,kind,lot,expiry_date,reference_code,notes,performed_by,transfer_uuid) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$product,$warehouse,milli_to_qty($qty),$kind,$lot,$expiry,$reference,$notes,$actor,$transferId]);
    }
    public function move(int $product,int $warehouse,string $quantity,string $kind,string $lot,?string $expiry,string $reference,string $notes,int $actor): void {
        if (!in_array($kind,['opening','receipt','issue','adjustment'],true)) throw new InvalidArgumentException('Unsupported movement type.');
        $delta=qty_to_milli($quantity,$kind==='adjustment');
        if ($delta===0) throw new InvalidArgumentException('Quantity cannot be zero.');
        if ($kind==='issue') $delta=-abs($delta);
        $this->db->beginTransaction();
        try {
            $this->lockProduct($product); $this->checkWarehouse($warehouse);
            $balance=$this->available($product,$warehouse,$lot);
            if ($balance+$delta<0) throw new DomainException('Insufficient stock in selected lot.');
            $this->record($product,$warehouse,$delta,$kind,$lot,$expiry,$reference,$notes,$actor);
            $this->db->commit();
        } catch(Throwable $e){ $this->db->rollBack(); throw $e; }
    }
    public function transfer(int $product,int $from,int $to,string $quantity,string $lot,string $reference,int $actor): void {
        $delta=qty_to_milli($quantity);
        if ($delta<=0 || $from===$to) throw new InvalidArgumentException('A positive quantity and two different warehouses are required.');
        $this->db->beginTransaction();
        try {
            $this->lockProduct($product); $this->checkWarehouse($from); $this->checkWarehouse($to);
            if ($this->available($product,$from,$lot)<$delta) throw new DomainException('Insufficient stock for transfer.');
            // Reuse the lot expiry from its latest incoming entry, if known.
            $q=$this->db->prepare('SELECT expiry_date FROM stock_movements WHERE product_id=? AND warehouse_id=? AND lot=? AND expiry_date IS NOT NULL ORDER BY id DESC LIMIT 1');
            $q->execute([$product,$from,$lot]); $expiry=$q->fetchColumn() ?: null;
            $uuid=bin2hex(random_bytes(16));
            $this->record($product,$from,-$delta,'transfer_out',$lot,$expiry,$reference,'Transfer to warehouse '.$to,$actor,$uuid);
            $this->record($product,$to,$delta,'transfer_in',$lot,$expiry,$reference,'Transfer from warehouse '.$from,$actor,$uuid);
            $this->db->commit();
        } catch(Throwable $e){ $this->db->rollBack(); throw $e; }
    }
    public function balances(): array {
        return $this->db->query('SELECT p.sku,p.name,p.unit,p.reorder_level,w.code AS warehouse,w.name AS warehouse_name,m.lot, SUM(m.qty_change) AS balance, MAX(CASE WHEN m.expiry_date IS NOT NULL THEN m.expiry_date END) AS expiry_date FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN warehouses w ON w.id=m.warehouse_id GROUP BY m.product_id,m.warehouse_id,m.lot HAVING balance <> 0 ORDER BY p.name,w.name,m.lot')->fetchAll();
    }
}
