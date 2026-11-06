import unittest,tempfile
from pathlib import Path
from app import connect,init,add,move,report
class TestInventory(unittest.TestCase):
 def test_sale_and_rollback(self):
  with tempfile.TemporaryDirectory() as t:
   with connect(str(Path(t)/"db.sqlite")) as c:
    init(c);add(c,"Regular",120)
    move(c,1,"receive",100)
    self.assertEqual(move(c,1,"sell",20),2400)
    with self.assertRaises(ValueError):move(c,1,"sell",100)
    self.assertEqual(report(c)["items"][0]["quantity"],80)
 def test_invalid(self):
  with tempfile.TemporaryDirectory() as t:
   with connect(str(Path(t)/"db.sqlite")) as c:
    init(c);add(c,"Regular",100)
    with self.assertRaises(ValueError):move(c,1,"receive",-1)
if __name__=="__main__":unittest.main()
