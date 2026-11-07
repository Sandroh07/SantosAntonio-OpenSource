import argparse, csv, io, sqlite3
from datetime import date
SCHEMA="""CREATE TABLE IF NOT EXISTS learners(id INTEGER PRIMARY KEY,name TEXT NOT NULL UNIQUE);
CREATE TABLE IF NOT EXISTS sessions(id INTEGER PRIMARY KEY,title TEXT NOT NULL,held_on TEXT NOT NULL,UNIQUE(title,held_on));
CREATE TABLE IF NOT EXISTS attendance(session_id INTEGER NOT NULL REFERENCES sessions(id),learner_id INTEGER NOT NULL REFERENCES learners(id),status TEXT NOT NULL CHECK(status IN ('present','absent','excused')),PRIMARY KEY(session_id,learner_id));"""
def connect(path):
 db=sqlite3.connect(path);db.execute("PRAGMA foreign_keys=ON");db.executescript(SCHEMA);return db
def learner(db,name):
 name=name.strip()
 if not 1<=len(name)<=120:raise ValueError("Invalid name")
 with db:return db.execute("INSERT INTO learners(name) VALUES(?)",(name,)).lastrowid
def session(db,title,held_on):
 title=title.strip()
 if not title or len(title)>120 or date.fromisoformat(held_on).isoformat()!=held_on:raise ValueError("Invalid session")
 with db:return db.execute("INSERT INTO sessions(title,held_on) VALUES(?,?)",(title,held_on)).lastrowid
def mark(db,sid,lid,status):
 if status not in ("present","absent","excused"):raise ValueError("Invalid status")
 with db:db.execute("INSERT INTO attendance VALUES(?,?,?) ON CONFLICT(session_id,learner_id) DO UPDATE SET status=excluded.status",(sid,lid,status))
def report(db):
 return db.execute("""SELECT l.id,l.name,COUNT(a.status),COALESCE(SUM(a.status='present'),0),COALESCE(SUM(a.status='excused'),0) FROM learners l LEFT JOIN attendance a ON l.id=a.learner_id GROUP BY l.id ORDER BY l.name""").fetchall()
def export_csv(db):
 out=io.StringIO();w=csv.writer(out);w.writerow(["id","name","marked","present","excused"]);w.writerows(report(db));return out.getvalue()
def main():
 p=argparse.ArgumentParser();p.add_argument("--db",default="attendance.sqlite3");sub=p.add_subparsers(dest="cmd",required=True)
 x=sub.add_parser("learner");x.add_argument("name")
 x=sub.add_parser("session");x.add_argument("title");x.add_argument("date")
 x=sub.add_parser("mark");x.add_argument("session_id",type=int);x.add_argument("learner_id",type=int);x.add_argument("status",choices=["present","absent","excused"])
 sub.add_parser("csv")
 a=p.parse_args()
 with connect(a.db) as db:
  if a.cmd=="learner":print(learner(db,a.name))
  elif a.cmd=="session":print(session(db,a.title,a.date))
  elif a.cmd=="mark":mark(db,a.session_id,a.learner_id,a.status);print("OK")
  else:print(export_csv(db),end="")
if __name__=="__main__":main()
