<?php
/** Identification behaviour; no database or runtime required. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require __DIR__.'/../classes/practicerules_class_inc.php';
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
$aliases=practicerules::aliases('African Fish Eagle',"Haliaeetus vocifer\nAfrican fish eagle");
check(count($aliases)===2,'Duplicate aliases');
check(practicerules::matches("  AFRICAN   fish\teagle  ",$aliases),'Case and whitespace');
check(practicerules::matches('Haliaeetus vocifer',$aliases),'Scientific name');
check(!practicerules::matches('eagle',$aliases),'Ambiguous name accepted');
check(!practicerules::matches('African fish egle',$aliases),'Typo accepted');
check(practicerules::normalise('Verreaux’s eagle')==="verreaux's eagle",'Apostrophe');
$set=practicerules::select(range(1,20),10);
check(count($set)===10 && count(array_unique($set))===10,'Selection size/duplicates');
foreach ([0,21,51] as $count) {
    try { practicerules::select(range(1,20),$count); throw new RuntimeException('Invalid count accepted'); }
    catch (DomainException $e) { check($e->getMessage()==='coverage','Wrong error'); }
}
echo "PASS aliases, exact matching, Unicode punctuation, distinct selection and insufficient bank rejection\n";
