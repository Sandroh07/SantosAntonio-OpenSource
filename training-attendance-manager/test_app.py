import tempfile,unittest,sqlite3
from app import *
class Tests(unittest.TestCase):
 def test_attendance(self):
  with tempfile.TemporaryDirectory() as d:
   with connect(d+"/a.db") as db:
    l=learner(db,"Ana");s=session(db,"Excel","2026-11-07");mark(db,s,l,"present");mark(db,s,l,"present")
    self.assertEqual(report(db)[0][3],1)
    self.assertIn("Ana",export_csv(db))
    with self.assertRaises(ValueError):mark(db,s,l,"invalid")
    with self.assertRaises(sqlite3.IntegrityError):mark(db,99,99,"present")
if __name__=="__main__":unittest.main()
