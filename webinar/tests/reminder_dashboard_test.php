<?php
/** Synthetic end-to-end queue/policy/dashboard checks; no real mail transport. */
require __DIR__.'/database_fixture.php';
foreach(['tbl_webinar_records','tbl_webinar_registrations','tbl_audience_contacts','tbl_audience_limits','tbl_audience_consent','tbl_audience_tokens','tbl_audience_campaigns','tbl_communications_outbox','tbl_communications_worker_state'] as $name)$table->executePrepared('DROP TABLE IF EXISTS '.$name);
$schema=[__DIR__.'/../sql/tbl_webinar_records.sql',__DIR__.'/../sql/tbl_webinar_registrations.sql'];
foreach(['contacts','limits','consent','tokens','campaigns'] as $name)$schema[]=__DIR__.'/../../audience/sql/tbl_audience_'.$name.'.sql';
foreach(['outbox','worker_state'] as $name)$schema[]='/framework/app/core_modules/communications/sql/tbl_communications_'.$name.'.sql';
foreach($schema as $file){
 include $file;$cols=[];
 foreach($fields as $name=>$f){$type=match($f['type']){'integer'=>'BIGINT','clob'=>'LONGTEXT','timestamp'=>'DATETIME',default=>isset($f['length'])?'VARCHAR('.$f['length'].')':'TEXT'};$cols[]='`'.$name.'` '.$type.(!empty($f['notnull'])?' NOT NULL':' NULL');}
 $table->executePrepared('CREATE TABLE '.$tablename.' ('.implode(',',$cols).') ENGINE=InnoDB');
}
foreach(['tbl_webinar_records'=>['id','source_key'],'tbl_audience_contacts'=>['id','email'],'tbl_audience_campaigns'=>['id'],'tbl_communications_outbox'=>['id','idempotency_key']] as $name=>$cols)foreach($cols as $col)$table->executePrepared('ALTER TABLE '.$name.' ADD UNIQUE KEY '.($name==='tbl_webinar_records'?'webinar_':'fixture_').($col==='source_key'?'source':$col).'_unique ('.$col.')');
foreach(['webinarreminders','webinarannouncements','webinarstore','webinareditstore','webinareditservice','webinarrenderer','communicationpolicy'] as $class)require __DIR__.'/../classes/'.$class.'_class_inc.php';
foreach(['audienceadmin','audiencecampaigns','audiencecommunicationpolicy','audiencerenderer'] as $class)require __DIR__.'/../../audience/classes/'.$class.'_class_inc.php';
foreach(['communicationservice','communicationdeliverygate'] as $class)require '/framework/app/core_modules/communications/classes/'.$class.'_class_inc.php';
$config=new class{public $values=['WEBINAR_DAY_ANNOUNCEMENTS_ENABLED'=>'TRUE','WEBINAR_DAY_ANNOUNCEMENTS_SINCE'=>'1','COMMUNICATION_FROM_EMAIL'=>'sender@example.invalid','COMMUNICATION_FROM_NAME'=>'Fixture','COMMUNICATION_ALLOWED_RECIPIENTS'=>''];function getValue($k,$m,$d=null){return $this->values[$k]??$d;}};
$texts=[];foreach(['webinar','audience'] as $module)foreach(file('/modules/'.$module.'/register.conf') as $line)if(str_starts_with($line,'TEXT: ')){[$key,$description,$value]=explode('|',trim(substr($line,6)),3);$texts[$key]=str_replace(['[-webinar-]','[-webinars-]'],['webinar','webinars'],$value);}
$GLOBALS['services']['language']=new class($texts){function __construct(public $texts){}function code2Txt($key,$module){return $this->texts[$key]??$key;}};
$GLOBALS['services']['altconfig']=new class{function getSiteRoot(){return 'https://example.invalid/';}function getSiteName(){return 'Synthetic site';}};
$GLOBALS['services']['dbsysconfig']=$config;
foreach(['webinarreminders'=>'tbl_webinar_records','webinareditstore'=>'tbl_webinar_records','webinarstore'=>'tbl_webinar_records','audienceadmin'=>'tbl_audience_contacts','audiencecampaigns'=>'tbl_audience_campaigns','communicationservice'=>'tbl_communications_outbox'] as $class=>$name)$GLOBALS['services'][$class]=repository($class,$name);
foreach(['webinarrenderer','audiencerenderer','webinarannouncements','communicationdeliverygate'] as $class){$o=new $class;$GLOBALS['services'][$class]=$o;}
$GLOBALS['services']['webinaremailimage']=new class{function thumbnail($r){return null;}};
$GLOBALS['services']['communicationservice']->objConfig=$config;
require __DIR__.'/../../audience/patches/installscripts_class_inc.php';
$hook=new audience_installscripts();$hook->postinstall();$hook->postinstall();
$reminders=$GLOBALS['services']['webinarreminders'];$campaigns=$GLOBALS['services']['audiencecampaigns'];
$service=new webinarregistrationservice;$service->init();$announcements=$GLOBALS['services']['webinarannouncements'];
$record=event(170);$record['presented_at']='2099-10-15 19:00:00';$payload=json_decode($record['payload'],true);$payload['timezone']='Africa/Johannesburg';$payload['joining_url']='https://meeting.example.invalid/SECRET-JOIN';$record['payload']=json_encode($payload);$record+=['source_key'=>'native|fixture','source_hash'=>str_repeat('a',64)];$table->insert($record);
$start=webinarschedule::start($record)->getTimestamp();$morning=$start-11*3600;
check($reminders->due($record,'subscribers')===$morning&&$reminders->due($record,'morning')===$morning,'Both morning sends at 08 SAST');
check($reminders->due($record,'ninety')===$start-5400,'90 minutes lead');
foreach(['one','two'] as $name)$audience->insert(['id'=>md5($name),'email'=>$name.'@example.invalid','name'=>$name,'state'=>'subscribed','revision'=>0,'created_at'=>'2099-10-01 00:00:00']);
$regs->insert(['id'=>str_repeat('b',32),'webinar_id'=>$id,'contact_id'=>md5('one'),'state'=>'confirmed','confirm_hash'=>str_repeat('c',64),'expires_at'=>$start,'contact_revision'=>0,'created_at'=>'2099-10-01 00:00:00','confirmed_at'=>'2099-10-01 00:00:00']);
$initial=$reminders->progress($record,$morning-1);check($initial['subscribers']['pending']===2&&$initial['morning']['pending']===1,'Pending recipient estimates');
check($announcements->run($morning-1)['scheduled']===0,'Not before due');
$announcements->run($morning+1);$announcements->run($morning+2);check(count($table->selectPrepared('SELECT * FROM tbl_audience_campaigns'))===1,'Idempotent automatic campaign');
$service->reminders($morning+1);$service->reminders($morning+2);$campaigns->pump(20);
$outbox=$table->selectPrepared('SELECT * FROM tbl_communications_outbox');check(count($outbox)===3,'Two general and one attendee email');
foreach($outbox as $message){$m=json_decode($message['metadata_json'],true);if($m['policy_module']==='webinar')check(str_contains($message['body_text'],'SECRET-JOIN'),'Registered reminder contains joining link');else check(!str_contains($message['body_text'],'SECRET-JOIN')&&!str_contains($message['body_html']??'','SECRET-JOIN'),'General email excludes joining link');}
$progress=$reminders->progress($record,$morning+10);check($progress['subscribers']['queued']===2&&$progress['subscribers']['pending']===0&&$progress['morning']['queued']===1,'Queue totals');
$table->executePrepared("UPDATE tbl_communications_outbox SET status='sent' WHERE recipient=?",['one@example.invalid']);
$progress=$reminders->progress($record,$morning+20);check($progress['subscribers']['sent']===1&&$progress['morning']['sent']===1,'Sent totals by audience');
$policy=new communicationpolicy;$generalPolicy=new audiencecommunicationpolicy;
$registered=array_values(array_filter($outbox,fn($r)=>json_decode($r['metadata_json'],true)['policy_module']==='webinar'))[0];$general=array_values(array_filter($outbox,fn($r)=>json_decode($r['metadata_json'],true)['policy_module']==='audience'))[0];
check($policy->allows(json_decode($registered['metadata_json'],true)),'Enabled attendee policy');check($generalPolicy->allows(json_decode($general['metadata_json'],true)),'Enabled general policy');
$input=['morning_time'=>'08:00','lead_minutes'=>'90','ninety'=>'1','monday'=>'1'];
$saved=$reminders->save($id,webinareditservice::version($record),$input);
check(!$policy->allows(json_decode($registered['metadata_json'],true))&&!$generalPolicy->allows(json_decode($general['metadata_json'],true)),'Disable stops both queued streams');
check(count($table->selectPrepared('SELECT * FROM tbl_communications_outbox'))===3,'Saving sends nothing');
rejects(fn()=>$reminders->save($id,webinareditservice::version($record),$input),'editor_conflict');
$user->admin=false;rejects(fn()=>$reminders->progress($record),'editor_forbidden');rejects(fn()=>$reminders->save($id,'',$input),'editor_forbidden');$user->admin=true;
$bad=$input;$bad['lead_minutes']='0';rejects(fn()=>$reminders->save($id,webinareditservice::version($saved),$bad),'reminder_invalid');
$bad=$input;$bad['morning_time']='99:99';rejects(fn()=>$reminders->save($id,webinareditservice::version($saved),$bad),'reminder_invalid');
$input['morning']='1';$input['subscribers']='1';$input['lead_minutes']='120';$saved=$reminders->save($id,webinareditservice::version($saved),$input);check($reminders->due($saved,'ninety')===$start-7200,'Lead time control');
check($reminders->settings($saved)['subscribers_since']>=time()-5,'Activation timestamp recorded');
$other=$saved;$p=json_decode($other['payload'],true);$p['timezone']='Europe/London';$other['payload']=json_encode($p);$d=$reminders->due($other,'morning');check((new DateTimeImmutable('@'.$d))->setTimezone(new DateTimeZone('Africa/Johannesburg'))->format('H:i')==='08:00','Morning always SAST');
echo 'PASS '.$engine.': both morning audiences, private joining link, schedule/deduplication, real outbox, dashboard counts, controls, stale saves and permissions.'.PHP_EOL;
