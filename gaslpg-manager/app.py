import argparse, sqlite3, json, datetime
from contextlib import contextmanager
@contextmanager
def connect(path):
 c=sqlite3.connect(path,isolation_level=None,timeout=10)
 try: yield c
 finally:c.close()
def init(c):
 c.executescript("CREATE TABLE IF NOT EXISTS items(id INTEGER PRIMARY KEY,name TEXT UNIQUE NOT NULL,qty INTEGER NOT NULL DEFAULT 0 CHECK(qty>=0),price INTEGER NOT NULL CHECK(price>=0));CREATE TABLE IF NOT EXISTS movements(id INTEGER PRIMARY KEY,item_id INTEGER NOT NULL REFERENCES items(id),kind TEXT NOT NULL,delta INTEGER NOT NULL,total INTEGER NOT NULL,created TEXT NOT NULL);")
def add(c,name,price):
 if not name.strip() or price<0:raise ValueError("invalid item")
 c.execute("INSERT INTO items(name,price) VALUES(?,?)",(name,price))
def move(c,item,kind,quantity):
 if kind not in ("receive","sell","adjust") or quantity==0 or kind!="adjust" and quantity<0:raise ValueError("invalid movement")
 c.execute("BEGIN IMMEDIATE")
 try:
  r=c.execute("SELECT qty,price FROM items WHERE id=?",(item,)).fetchone()
  if r is None:raise ValueError("unknown item")
  delta=-quantity if kind=="sell" else quantity
  if r[0]+delta<0:raise ValueError("insufficient stock")
  c.execute("UPDATE items SET qty=qty+? WHERE id=?",(delta,item))
  total=quantity*r[1] if kind=="sell" else 0
  c.execute("INSERT INTO movements(item_id,kind,delta,total,created) VALUES(?,?,?,?,?)",(item,kind,delta,total,datetime.datetime.now(datetime.timezone.utc).isoformat()))
  c.execute("COMMIT")
  return total
 except Exception:
  c.execute("ROLLBACK")
  raise
def report(c):
 return {"items":[{"id":r[0],"name":r[1],"quantity":r[2],"price_minor":r[3]} for r in c.execute("SELECT id,name,qty,price FROM items")],"sales_total_minor":c.execute("SELECT COALESCE(SUM(total),0) FROM movements WHERE kind='sell'").fetchone()[0]}
def main():
 p=argparse.ArgumentParser()
 p.add_argument("--db",default="operations.sqlite3")
 s=p.add_subparsers(dest="cmd",required=True)
 s.add_parser("init");a=s.add_parser("item");a.add_argument("name");a.add_argument("price",type=int)
 m=s.add_parser("move");m.add_argument("item",type=int);m.add_argument("kind",choices=["receive","sell","adjust"]);m.add_argument("quantity",type=int)
 s.add_parser("report");v=p.parse_args()
 with connect(v.db) as c:
  init(c)
  if v.cmd=="item":add(c,v.name,v.price);out={"created":True}
  elif v.cmd=="move":out={"total_minor":move(c,v.item,v.kind,v.quantity)}
  elif v.cmd=="report":out=report(c)
  else:out={"initialized":True}
 print(json.dumps(out,indent=2))
if __name__=="__main__":main()
