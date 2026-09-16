<?php
/** Run only in the disposable local runtime: SPOKEN_LOCAL_TEST=1 php .../tests/storage_integration.php */
if (PHP_SAPI!=='cli' || getenv('SPOKEN_LOCAL_TEST')!=='1') exit(64);
$root=dirname(__DIR__,3);chdir($root);$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI';$_SERVER['HTTP_HOST']='localhost';$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';$engine=new engine();$store=$engine->getObject('spokenstore','spokenassessment');
if (($argv[1]??'')==='claim') { $row=$store->claim();echo json_encode($row?['id'=>$row['id'],'token'=>$row['claim_token'],'state'=>$row['state']]:null);exit; }
if (($argv[1]??'')==='read') { echo json_encode((bool)$store->activity($argv[2]));exit; }
function test($ok,$message){if(!$ok)throw new RuntimeException($message);}
// Refuse to interfere with queued work belonging to anyone else.
test(!$store->getArray("SELECT id FROM tbl_spoken_attempts WHERE state IN ('queued_transcription','queued_feedback','transcribing','feedback_processing')"),'Queue must be empty before this local test');
$id=bin2hex(random_bytes(16));$attempt=bin2hex(random_bytes(16));
try {
 $now=$store->now();
 $store->transaction(fn()=>$store->putActivity('', ['id'=>$id,'contextcode'=>'disposable_spoken_test','userid'=>'fixture','title'=>'Unicode 🌿','prompt'=>'Evidence','outcomes'=>'Explain','rubric_id'=>'','published'=>1,'version'=>1,'date_created'=>$now,'date_updated'=>$now]));
 test($store->activity($id)['title']==='Unicode 🌿','Unicode persistence');
 $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' read '.escapeshellarg($id);
 test(trim(shell_exec($command))==='true','Persistence across independent processes');
 try {$store->transaction(function()use($store,$id){$store->putActivity($id,['title'=>'Rolled back']);throw new DomainException('rollback');});}catch(DomainException $e){}
 test($store->activity($id)['title']==='Unicode 🌿','Transaction rollback');
 $store->addAttempt(['id'=>$attempt,'activity_id'=>$id,'contextcode'=>'disposable_spoken_test','userid'=>'fixture','state'=>'queued_transcription','claim_token'=>'','snapshot_json'=>'{}','original_transcript'=>'','approved_transcript'=>'','feedback_json'=>'{}','teacher_feedback'=>'','teacher_id'=>'','reflection'=>'','error_code'=>'','transcription_model'=>'','feedback_model'=>'','duration_seconds'=>1,'date_created'=>$now,'date_updated'=>$now]);
 $duplicate=false;try{$store->addAttempt($store->attempt($attempt));}catch(RuntimeException $e){$duplicate=true;}test($duplicate,'Duplicate attempt identity rejected');
 $children=[];
 for($i=0;$i<2;$i++){ $pipes=[];$process=proc_open([PHP_BINARY,__FILE__,'claim'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$children[]=[$process,$pipes]; }
 $claims=[];foreach($children as [$process,$pipes]){$claims[]=json_decode(stream_get_contents($pipes[1]),true);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);test(proc_close($process)===0 && $error==='','Worker process');}
 $claims=array_values(array_filter($claims));test(count($claims)===1 && $claims[0]['id']===$attempt,'Only one competing worker claims the attempt');
 $claim=$store->attempt($attempt);$wrong=$claim;$wrong['claim_token']=str_repeat('0',32);
 test(!$store->finish($wrong,['state'=>'feedback_ready']),'Stale worker cannot write');
 test($store->finish($claim,['state'=>'transcript_ready','original_transcript'=>'Original 🌿']),'Claim completes');
 test(!$store->finish($claim,['state'=>'feedback_ready']),'Completed claim cannot replay');
 test($store->attempt($attempt)['original_transcript']==='Original 🌿','Original retained');
 echo "PASS real database persistence, Unicode, rollback, competing worker claims, stale-token rejection and replay rejection.\n";
} finally {
 $store->query('DELETE FROM tbl_spoken_attempts WHERE id='.$store->q($attempt));
 $store->query('DELETE FROM tbl_spoken_activities WHERE id='.$store->q($id));
}
