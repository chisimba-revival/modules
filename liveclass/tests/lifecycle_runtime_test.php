<?php
$GLOBALS['kewl_entry_point_run']=true; class ChisimbaObject{}; require 'modules/liveclass/classes/liveclasslifecycle_class_inc.php';
final class FakeConfig{function getValue($a,$b){return 15;}}
class TestLifecycle extends liveclasslifecycle{function getObject($a,$b=null){return new FakeConfig();}} $life=new TestLifecycle();$life->init();
$session=array('starts_at'=>'2026-09-07 12:00:00','duration_minutes'=>60,'status'=>'scheduled');$start=strtotime($session['starts_at']);
$tests=array('locked before window'=>!$life->state($session,$start-901)['can_join'],'opens at fifteen minutes'=>$life->state($session,$start-900)['can_join'],'remains open during session'=>$life->state($session,$start+3599)['can_join'],'closes after session'=>!$life->state($session,$start+3601)['can_join']);foreach($tests as $label=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";}
