import argparse,csv,io,sqlite3
from datetime import datetime,timezone
SCHEMA="""CREATE TABLE IF NOT EXISTS tickets(id INTEGER PRIMARY KEY,title TEXT NOT NULL,description TEXT NOT NULL,priority TEXT NOT NULL CHECK(priority IN ('low','normal','high')),status TEXT NOT NULL,created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS events(id INTEGER PRIMARY KEY,ticket_id INTEGER NOT NULL REFERENCES tickets(id),old_status TEXT,new_status TEXT NOT NULL,changed_at TEXT NOT NULL);"""
ALLOWED={"open":{"in_progress","resolved"},"in_progress":{"open","resolved"},"resolved":{"in_progress","closed"},"closed":set()}
def connect(path):
 db=sqlite3.connect(path);db.execute("PRAGMA foreign_keys=ON");db.executescript(SCHEMA);return db
def now():return datetime.now(timezone.utc).isoformat()
def create(db,title,description,priority="normal"):
 title=title.strip();description=description.strip()
 if not 1<=len(title)<=160 or len(description)>4000 or priority not in ("low","normal","high"):raise ValueError("Invalid input")
 with db:
  cur=db.execute("INSERT INTO tickets(title,description,priority,status,created_at) VALUES(?,?,?,?,?)",(title,description,priority,"open",now()))
  db.execute("INSERT INTO events(ticket_id,old_status,new_status,changed_at) VALUES(?,?,?,?)",(cur.lastrowid,None,"open",now()))
  return cur.lastrowid
def transition(db,ticket_id,status):
 with db:
  row=db.execute("SELECT status FROM tickets WHERE id=?",(ticket_id,)).fetchone()
  if row is None or status not in ALLOWED[row[0]]:raise ValueError("Invalid transition")
  db.execute("UPDATE tickets SET status=? WHERE id=?",(status,ticket_id))
  db.execute("INSERT INTO events(ticket_id,old_status,new_status,changed_at) VALUES(?,?,?,?)",(ticket_id,row[0],status,now()))
def list_tickets(db,status=None):
 if status is not None and status not in ALLOWED:raise ValueError("Invalid status")
 return db.execute("SELECT id,title,priority,status,created_at FROM tickets WHERE (? IS NULL OR status=?) ORDER BY id",(status,status)).fetchall()
def export_csv(db):
 out=io.StringIO();w=csv.writer(out);w.writerow(["id","title","priority","status","created_at"]);w.writerows(list_tickets(db));return out.getvalue()
def main():
 p=argparse.ArgumentParser();p.add_argument("--db",default="servicedesk.sqlite3");sub=p.add_subparsers(dest="cmd",required=True)
 x=sub.add_parser("new");x.add_argument("title");x.add_argument("description");x.add_argument("--priority",choices=["low","normal","high"],default="normal")
 x=sub.add_parser("status");x.add_argument("id",type=int);x.add_argument("new_status",choices=list(ALLOWED))
 x=sub.add_parser("list");x.add_argument("--status",choices=list(ALLOWED))
 sub.add_parser("csv")
 a=p.parse_args()
 with connect(a.db) as db:
  if a.cmd=="new":print(create(db,a.title,a.description,a.priority))
  elif a.cmd=="status":transition(db,a.id,a.new_status);print("OK")
  elif a.cmd=="list":
   for row in list_tickets(db,a.status):print(*row,sep=" | ")
  else:print(export_csv(db),end="")
if __name__=="__main__":main()
