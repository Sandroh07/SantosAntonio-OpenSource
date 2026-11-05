-- OPTIONAL demo catalog. Do not import into a live business unless these are actual products.
INSERT IGNORE INTO categories(name) VALUES ('General'),('Services');
INSERT INTO products(name,sku,barcode,category_id,item_type,price_cents,stock_qty,low_stock_threshold,active)
VALUES
('Sample bottled water','DEMO-WATER','000000001',(SELECT id FROM categories WHERE name='General'),'product',35000,50,10,1),
('Sample consulting hour','DEMO-CONSULT','000000002',(SELECT id FROM categories WHERE name='Services'),'service',1000000,0,0,1)
ON DUPLICATE KEY UPDATE id=id;
