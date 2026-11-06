# GasLPG Manager

Offline stock and sales proof of concept for LPG products. Quantities are integer **grams**, prices integer minor currency units **per grams**. Persistent SQLite storage, atomic stock movements, non-negative inventory, sales totals and tests.

## Run

```bash
python app.py --db demo.sqlite3 init
python app.py --db demo.sqlite3 item Regular 120
python app.py --db demo.sqlite3 move 1 receive 100000
python app.py --db demo.sqlite3 move 1 sell 5000
python app.py --db demo.sqlite3 report
python -m unittest -v
```

Cylinder custody, tare weight, serial tracking and deposit management are not included. Daily fiscal reconciliation, safety compliance and production integrations require independent work. This is not certified or deployed software.

See SECURITY.md, SUPPORT.md and LICENSE.
