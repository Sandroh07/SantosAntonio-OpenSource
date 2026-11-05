-- SmartInventory v1 / MySQL 8+, UTF8MB4
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator','viewer') NOT NULL DEFAULT 'viewer',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'unit',
  reorder_level DECIMAL(16,3) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (reorder_level >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  qty_change DECIMAL(16,3) NOT NULL,
  kind ENUM('opening','receipt','issue','adjustment','transfer_in','transfer_out') NOT NULL,
  lot VARCHAR(80) NOT NULL DEFAULT '',
  expiry_date DATE NULL,
  reference_code VARCHAR(120) NOT NULL DEFAULT '',
  notes VARCHAR(300) NOT NULL DEFAULT '',
  transfer_uuid CHAR(32) NULL,
  performed_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  FOREIGN KEY (performed_by) REFERENCES users(id),
  CHECK (qty_change <> 0),
  INDEX ix_stock_product_wh_lot (product_id, warehouse_id, lot),
  INDEX ix_stock_created (created_at),
  INDEX ix_stock_expiry (expiry_date),
  INDEX ix_stock_transfer (transfer_uuid)
) ENGINE=InnoDB;
-- Stock is derived exclusively from immutable stock_movements. Do not UPDATE stock_movements.
