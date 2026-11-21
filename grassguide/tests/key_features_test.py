import sys,unittest,pathlib
sys.path.insert(0,str(pathlib.Path(__file__).resolve().parents[1]/'scripts'))
from key_features import highlights,candidates,state_codes
class HighlightsTest(unittest.TestCase):
    def test_defaults_missing_and_qualifiers_not_silently_assumed(self):
        self.assertIsNone(state_codes('1 <i>'))
        self.assertIsNone(state_codes('NA'))
        self.assertFalse(candidates({'panicle_type':'1 <sometimes>'}))
        self.assertEqual(highlights('A a',{'A a':{}}),'')
    def test_alternatives_ranges_and_units_preserved(self):
        row={'panicle_type':'1&2','panicle_length':'(5-)10-20(-30)','principalLemmaAwn_lengthIncludingColumn':'25-70','lemmaApex_numberOfAwns':'3','spikelets_length':'6-11','leafBlades_width':'1-8','culms_height':'30-200'}
        value=highlights('A a',{'A a':row})
        self.assertIn('open, spreading panicle or a contracted panicle',value)
        self.assertIn('(5–)10–20(–30) cm',value)
        self.assertIn('25–70 mm',value)
        self.assertEqual(len(value.splitlines()),4)
        self.assertNotIn('Stem height',value)
if __name__=='__main__':unittest.main()
