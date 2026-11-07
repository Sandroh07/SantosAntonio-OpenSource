import tempfile,unittest
from app import *
class Tests(unittest.TestCase):
 def test_workflow(self):
  with tempfile.TemporaryDirectory() as d:
   with connect(d+"/t.db") as db:
    t=create(db,"Printer","Paper jam","high")
    with self.assertRaises(ValueError):transition(db,t,"closed")
    transition(db,t,"in_progress");transition(db,t,"resolved");transition(db,t,"closed")
    self.assertEqual(list_tickets(db,"closed")[0][0],t)
    self.assertEqual(db.execute("SELECT COUNT(*) FROM events").fetchone()[0],4)
    self.assertIn("Printer",export_csv(db))
if __name__=="__main__":unittest.main()
