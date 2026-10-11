<?php
require __DIR__.'/database_fixture.php';
if(($argv[1]??'')==='worker'){
 $config->verify=($argv[3]??'')==='pending';putenv('WEBINAR_RACE_DELAY=1');
 try{echo register($argv[2]);}catch(DomainException $e){echo $e->getMessage();}exit;
}
foreach([
 'tbl_webinar_records'=>'id VARCHAR(32) PRIMARY KEY, kind VARCHAR(16), status VARCHAR(16), title TEXT, presented_at DATETIME, payload TEXT',
 'tbl_webinar_registrations'=>'id VARCHAR(32) PRIMARY KEY, webinar_id VARCHAR(32), contact_id VARCHAR(32), state VARCHAR(16), confirm_hash VARCHAR(64), expires_at BIGINT, contact_revision INT, created_at DATETIME, confirmed_at DATETIME NULL, UNIQUE KEY event_contact(webinar_id,contact_id)',
 'tbl_audience_contacts'=>'id VARCHAR(32) PRIMARY KEY, email VARCHAR(254) UNIQUE, name VARCHAR(200), state VARCHAR(16), revision INT, created_at DATETIME, verified_at DATETIME NULL',
 'tbl_audience_limits'=>'id VARCHAR(32) PRIMARY KEY, attempts INT, expires_at BIGINT',
 'tbl_audience_consent'=>'id VARCHAR(32) PRIMARY KEY, contact_id VARCHAR(32), action VARCHAR(40), source TEXT, recorded_at DATETIME, wording TEXT',
 'fixture_outbox'=>'id VARCHAR(32) PRIMARY KEY, kind VARCHAR(20), token VARCHAR(64), registration_id VARCHAR(32)'
] as $name=>$columns){$table->executePrepared('DROP TABLE IF EXISTS '.$name);$table->executePrepared('CREATE TABLE '.$name.' ('.$columns.') ENGINE=InnoDB');}
resetFixture();check(register('one@example.invalid')==='confirmed','First place');
check(register('one@example.invalid')==='already_registered','Duplicate at capacity');
rejects(fn()=>register('two@example.invalid'),'full');
check(count($table->selectPrepared('SELECT * FROM fixture_outbox'))===1,'No duplicate/rejected email');
check(count($table->selectPrepared('SELECT * FROM tbl_audience_contacts'))===1,'Rejected contact rolled back');
check($regs->countsForManagement([event()])===[$id=>1],'Management total');
$past=event();$past['presented_at']='2000-01-01';$draft=$past;$draft['status']='draft';
check($regs->countsForManagement([$draft])===[$id=>1]&&$regs->countsForUpcoming([$draft])===[],'Past/draft management counts without public disclosure');
$user->logged=false;check($regs->countsForManagement([event()])===[],'Guest denied');$user->logged=true;
// Capacity is loaded under lock, never taken from a stale page/form.
$table->update('id',$id,['payload'=>event(2)['payload']]);check(register('two@example.invalid')==='confirmed','Raised current limit');
$table->update('id',$id,['payload'=>event(1)['payload']]);rejects(fn()=>register('three@example.invalid'),'full');
check((int)$table->selectPrepared('SELECT COUNT(*) n FROM tbl_webinar_registrations')[0]['n']===2,'Lower cap preserves confirmations');
// Revoked consent no longer occupies a place; stale confirmed revision stays excluded.
$table->executePrepared("UPDATE tbl_audience_contacts SET state='unsubscribed',revision=revision+1");check(register('three@example.invalid')==='confirmed','Revoked consent releases capacity');
resetFixture();$config->verify=true;check(register('one@example.invalid')==='pending','Pending reserves place');
$token=$table->selectPrepared('SELECT token FROM fixture_outbox')[0]['token'];
rejects(fn()=>register('two@example.invalid'),'full');check($service->confirm($token)['id']===$id,'Own reservation confirms at cap');
check($service->confirm($token)['id']===$id,'Confirmation idempotent');
check(count($table->selectPrepared('SELECT * FROM fixture_outbox'))===2,'Only verification and confirmation');
resetFixture();register('one@example.invalid');$token=$table->selectPrepared('SELECT token FROM fixture_outbox')[0]['token'];
$table->executePrepared('UPDATE tbl_webinar_registrations SET expires_at=?',[time()-1]);check(register('two@example.invalid')==='pending','Expired pending releases place');rejects(fn()=>$service->confirm($token),'expired');
resetFixture(2);register('one@example.invalid');$token=$table->selectPrepared('SELECT token FROM fixture_outbox')[0]['token'];
$config->verify=false;register('two@example.invalid');$table->update('id',$id,['payload'=>event(1)['payload']]);rejects(fn()=>$service->confirm($token),'full');
check($regs->countsForManagement([event()])===[$id=>1],'Rejected confirmation not counted');
resetFixture();$service->fail=true;try{register('failure@example.invalid');throw new LogicException('Failure not raised');}catch(RuntimeException $e){check($e->getMessage()==='Synthetic outbox failure','Expected outbox failure');}$service->fail=false;
foreach(['tbl_webinar_registrations','tbl_audience_contacts','tbl_audience_consent','fixture_outbox'] as $name)check(!$table->selectPrepared('SELECT * FROM '.$name),'Atomic rollback '.$name);
// Caller ownership must survive a refused nested registration transaction.
$table->beginTransaction();try{register('nested@example.invalid');throw new RuntimeException('Nested transaction accepted');}catch(LogicException $e){check($table->hasActiveTransaction(),'Outer transaction preserved');}$table->rollbackTransaction();
// Two independent PHP connections compete for the final place, in each mode.
foreach(['confirmed','pending'] as $mode){
 resetFixture();$processes=[];
 foreach(['race1@example.invalid','race2@example.invalid'] as $email){$pipes=[];$p=proc_open([PHP_BINARY,'-d','auto_prepend_file=',__FILE__,'worker',$email,$mode],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$processes[]=[$p,$pipes];}
 $results=[];foreach($processes as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($p)===0&&$errors==='','Worker error '.$errors);}
 sort($results);$expected=[$mode,'full'];sort($expected);check($results===$expected,'Race results '.json_encode($results));
 check((int)$table->selectPrepared('SELECT COUNT(*) n FROM tbl_webinar_registrations')[0]['n']===1,'Race overbooked');
 check((int)$table->selectPrepared('SELECT COUNT(*) n FROM fixture_outbox')[0]['n']===1,'Race queued excess mail');
}
echo 'PASS '.$engine.': last-place races, duplicates, current limit, lowering/raising, pending/expiry, confirmation, consent, private totals, outbox rollback and transaction ownership.'.PHP_EOL;
