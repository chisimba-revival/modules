<?php
/** Real dbTable ordering checks, included by the disposable database suites. */
if (PHP_SAPI!=='cli'||getenv('CHISIMBA_DB_BASELINE')!=='1')exit(64);
$orderBoard=$boards->createBoard(['scopetype'=>'personal','scopeid'=>'ordering','ownerid'=>'ordering','title'=>'Ordering fixture']);
$otherBoard=$boards->createBoard(['scopetype'=>'personal','scopeid'=>'other','ownerid'=>'other','title'=>'Other fixture']);
foreach (['a'=>2147483647,'b'=>1,'c'=>1700000000] as $suffix=>$legacyRank) {
    $tasks->insert(['id'=>str_repeat($suffix,32),'boardid'=>$orderBoard,'title'=>$suffix,'status'=>'not_started','sortorder'=>$legacyRank,'datecreated'=>'2026-01-0'.(ord($suffix)-96).' 10:00:00','datemodified'=>'2026-01-03 10:00:00']);
}
$a=str_repeat('a',32);$b=str_repeat('b',32);$c=str_repeat('c',32);
$orderIds=fn()=>array_column(array_values(array_filter($tasks->forBoard($orderBoard),fn($row)=>$row['status']==='not_started')),'id');
ensureBaseline($orderIds()===[$a,$b,$c],'Legacy timestamp orders must default to creation order');
$tasks->moveTask($c,'not_started',$a,'not_started',[$a,$b,$c]);
ensureBaseline($orderIds()===[$c,$a,$b],'Move to top persists');
$tasks->moveTask($c,'not_started','', 'not_started',[$c,$a,$b]);
ensureBaseline($orderIds()===[$a,$b,$c],'Move to bottom persists');
$tasks->moveTask($c,'not_started',$b,'not_started',[$a,$b,$c]);
ensureBaseline($orderIds()===[$a,$c,$b],'Move to middle persists');
$before=$tasks->forBoard($orderBoard);
try{$tasks->moveTask($a,'not_started','', 'not_started',[$a,$b,$c]);throw new Exception('Stale reorder accepted');}catch(DomainException $expected){}
ensureBaseline($tasks->forBoard($orderBoard)===$before,'Conflict must not change stored order');
$foreign=$tasks->createTask(['boardid'=>$otherBoard,'status'=>'not_started','title'=>'Other board']);
try{$tasks->moveTask($a,'not_started',$foreign);throw new Exception('Foreign anchor accepted');}catch(DomainException $expected){}
$tasks->moveTask($c,'in_progress','', 'not_started',[]);
ensureBaseline($orderIds()===[$a,$b]&&$tasks->one($c)['status']==='in_progress','Cross-column move preserves other tasks');
$new=$tasks->createTask(['boardid'=>$orderBoard,'status'=>'not_started','title'=>'New task']);
$newer=$tasks->createTask(['boardid'=>$orderBoard,'status'=>'not_started','title'=>'Same-second task']);
ensureBaseline($orderIds()===[$a,$b,$new,$newer],'New tasks append in insertion order even within one second');
$before=$tasks->forBoard($orderBoard);
$table->executeExactPrepared("CREATE TRIGGER fixture_deny_rank BEFORE UPDATE ON tbl_kanban_tasks FOR EACH ROW BEGIN IF NEW.sortorder <> OLD.sortorder THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture rank failure'; END IF; END");
try{$tasks->moveTask($c,'not_started',$a);throw new LogicException('Rank failure ignored');}catch(LogicException $error){throw $error;}catch(Throwable $expected){}
$table->executeExactPrepared('DROP TRIGGER fixture_deny_rank');
ensureBaseline($tasks->forBoard($orderBoard)===$before,'Partial reorder and status changes must roll back');
$tasks->beginTransaction();$tasks->moveTask($newer,'not_started',$a);$tasks->rollbackTransaction();
ensureBaseline($tasks->forBoard($orderBoard)===$before,'Nested reorder preserves outer rollback');
echo "PASS Kanban ordering: chronology, top/middle/bottom, stale conflicts, board isolation, status moves, insertion order and rollback\n";
