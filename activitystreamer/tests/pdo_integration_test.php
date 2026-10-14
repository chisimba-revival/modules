<?php
/** Local-only integration test: all event fixtures are rolled back. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_ACTIVITY_TEST')!=='1')exit("Set CHISIMBA_ACTIVITY_TEST=1 in the local installation.\n");
$GLOBALS['kewl_entry_point_run']=true;$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='chisimba.test:8445';$_SERVER['SCRIPT_NAME']='/ch/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();
set_error_handler(function($n,$m,$f,$l){if(error_reporting()&$n)throw new ErrorException($m,0,$n,$f,$l);return false;});
$store=$engine->getObject('activitydb','activitystreamer');
$engine->loadClass('activitybrowser','activitystreamer');
class ActivityFixtureBrowser extends activitybrowser {
 public function mayView(string $mode):bool{return true;}
 public function contextCode():string{return 'qa-activity-local';}
}
$browser=new ActivityFixtureBrowser($engine,'activitystreamer');
function verify($ok,$label){if(!$ok)throw new RuntimeException($label);}
$before=(int)$store->selectPrepared('SELECT COUNT(*) AS total FROM tbl_activity')[0]['total'];
try{$store->transaction(function()use($store,$browser){
 for($i=0;$i<32;$i++){
  $notification=new class($i){function __construct(private $i){}function getNotificationName(){return 'fixture';}function getNotificationInfo(){return ['title'=>'QA activity '.$this->i,'description'=>"Quoted ' and <plain text>",'author'=>'qa-string-user-id','contextcode'=>'qa-activity-local'];}};
  verify(is_string($store->insertPost($notification)),'insert notification');
 }
 $store->insert(['module'=>'fixture','title'=>'Other scope','description'=>'private','contextcode'=>'qa-other-scope','createdon'=>'2026-01-01 00:00:00','createdby'=>'qa-string-user-id','link'=>'']);
 $a=$browser->browse('context');$b=$browser->browse('context',['page'=>2]);
 verify($a['total']===32&&count($a['rows'])===30&&count($b['rows'])===2,'scope/paging');
 verify(!array_intersect(array_column($a['rows'],'id'),array_column($b['rows'],'id')),'stable pages');
 verify($browser->browse('context',['q'=>"' and <plain text>"])['total']===32,'quoted search');
 verify($browser->browse('context',['q'=>"' OR 1=1 --"])['total']===0,'bound search');
 verify($browser->browse('pages')['total']===0,'empty course page query');
 verify($store->selectPrepared('SELECT createdby FROM tbl_activity WHERE contextcode=? LIMIT 1',['qa-activity-local'])[0]['createdby']==='qa-string-user-id','string author stored');
 throw new LogicException('rollback-fixtures');
});}catch(LogicException $done){if($done->getMessage()!=='rollback-fixtures')throw $done;}
verify((int)$store->selectPrepared('SELECT COUNT(*) AS total FROM tbl_activity')[0]['total']===$before,'rollback');
$ops=$engine->getObject('activityops','activitystreamer');verify($ops->postmade(new stdClass())===false,'recording remains disabled');
verify((int)$store->selectPrepared('SELECT COUNT(*) AS total FROM tbl_activity')[0]['total']===$before,'no automatic recording');
echo "PASS: PDO notification inserts, string authors, scoped/quoted search, stable paging, empty course pages, rollback and disabled recording.\n";
