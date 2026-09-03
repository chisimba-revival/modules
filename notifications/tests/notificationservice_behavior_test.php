<?php
/** Exercise notification boundaries without sending messages or touching storage. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {
    public $services=array();
    public function getObject($name,$module=null){return $this->services[$name];}
    public function uri($params,$module){return 'index.php?'.http_build_query(array_merge(array('module'=>$module),$params),'','&amp;');}
}
require __DIR__.'/../classes/notificationservice_class_inc.php';
require __DIR__.'/../../discussion/classes/discussionnotificationpublisher_class_inc.php';
$checks=0;
function check($ok,$label){global $checks;$checks++;if(!$ok)throw new RuntimeException($label);}
$events=new class {
    public $stored=array();public $backup;public $rolledBack=false;
    public function byKey($key){return $this->stored[$key]??false;}
    public function beginTransaction(){$this->backup=$this->stored;}
    public function createEvent($event){$this->stored[$event['idempotency_key']]=$event;return true;}
    public function commitTransaction(){}
    public function rollbackTransaction(){$this->stored=$this->backup;$this->rolledBack=true;}
};
$recipients=new class {
    public $stored=array();public $failWrite=false;public $readResult=true;public $lastRead;public $limit;
    public function addRecipient($row){if($this->failWrite)return false;$this->stored[]=$row;return true;}
    public function markRead($id,$user,$now){$this->lastRead=array($id,$user);return $this->readResult;}
    public function feed($user,$limit,$cursor,$unread){$this->limit=$limit;$rows=array();for($i=0;$i<2;$i++){$rows[]=array('notification_id'=>str_repeat((string)($i+1),32),'event_id'=>'event','event_type'=>'discussion.reply.created','state'=>'unread','actor_user_id'=>'author','context_code'=>'course','source_type'=>'discussion_post','source_id'=>'post','title'=>'Reply','summary'=>'New reply','target_url'=>'index.php','payload_json'=>'{}','datecreated'=>'2026-09-03 12:00:00','read_at'=>null);}return $rows;}
};
$service=new notificationservice();$service->services=array('dbnotificationevents'=>$events,'dbnotificationrecipients'=>$recipients);$service->init();
$publisher=new discussionnotificationpublisher();$publisher->services=array('notificationservice'=>$service,
    'courseawarelaunchservice'=>new class {public function target($course,$module,$params){return array('module'=>'context','params'=>array('action'=>'launchcourseactivity','coursecode'=>$course,'targetmodule'=>$module));}},
    'dbdiscussionsubscriptions'=>new class {public function recipientUserIds($id){return array('author','learner','learner');}},
    'dbtopicsubscriptions'=>new class {public function recipientUserIds($id){return array('learner','other');}});
$publisher->init();
$publish=fn($id)=>$publisher->postCreated($id,'topic','discussion','course','author','Author','Title','Discussion','index.php?a=1&amp;b=2',true);
$out=$publish('post');
check($out['ok']&&$out['recipientCount']===2,'Reply goes to unique subscribers excluding its author');
check(array_column($recipients->stored,'user_id')===array('learner','other'),'Topic and discussion subscribers are combined');
check(array_values($events->stored)[0]['target_url']==='index.php?module=context&action=launchcourseactivity&coursecode=course&targetmodule=discussion','Notification target uses course entry with usable query parameters');
$again=$publish('post');
check($again['code']==='already_published'&&count($recipients->stored)===2,'Retry does not duplicate recipient notifications');
$recipients->failWrite=true;$out=$publish('failed');
check(!$out['ok']&&$events->rolledBack&&!isset($events->stored['discussion-post:failed']),'Recipient failure rolls back the publication');
$id=str_repeat('a',32);$recipients->readResult=false;
check(!$service->markRead($id,'learner'),'Database read-update failure is reported');
check($recipients->lastRead===array($id,'learner'),'Read update carries the recipient ownership boundary');
$recipients->readResult=true;check($service->markRead($id,'learner'),'Successful read update is acknowledged');
check(!$service->markRead('invalid','learner'),'Malformed notification ID is rejected');
$page=$service->feed('learner',0);
check($recipients->limit===1&&count($page['items'])===1&&$page['page']['hasMore']&&$page['page']['nextCursor']!==null,'Invalid page size is normalised before pagination');
$service->feed('learner',500);check($recipients->limit===100,'Maximum page size remains bounded');
check(!$service->feed('learner',25,'not-a-cursor')['ok'],'Malformed pagination cursor is rejected');
echo "Notification service passed ($checks behavioural checks).\n";
