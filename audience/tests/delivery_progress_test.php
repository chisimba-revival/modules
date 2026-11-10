<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable{function getObject($name,$module){return $GLOBALS[$name];}}
require __DIR__.'/../classes/audiencecampaigns_class_inc.php';
class FixtureCampaigns extends audiencecampaigns{public $state='queued';function one($id,$lock=false){return ['state'=>$this->state,'payload'=>json_encode(['recipients'=>[1,2]])];}}
$GLOBALS['audienceadmin']=new class{public $counts=[];function guard(){}function q($v){return "'".$v."'";}function rows($s){return $this->counts;}};
$GLOBALS['audiencerenderer']=new class{function text($key){return $key==='mail_progress'?'%s / %s / %s':$key;}};
$c=new FixtureCampaigns;$id=str_repeat('a',32);
function ensure($v){if(!$v)throw new RuntimeException('Incorrect delivery progress');}
ensure($c->progress($id)['label']==='mail_queued');
$GLOBALS['audienceadmin']->counts=[['status'=>'sent','n'=>1],['status'=>'queued','n'=>1]];$c->state='dispatched';ensure($c->progress($id)['label']==='mail_sending');
$GLOBALS['audienceadmin']->counts=[['status'=>'sent','n'=>2]];ensure($c->progress($id)['label']==='mail_sent'&&$c->progress($id)['tone']==='success');
$GLOBALS['audienceadmin']->counts=[['status'=>'sent','n'=>1],['status'=>'failed','n'=>1]];ensure($c->progress($id)['label']==='mail_attention');
$GLOBALS['audienceadmin']->counts=[];ensure($c->progress($id)['label']==='mail_attention');
echo "PASS queued, sending, sent, failed and skipped delivery states.\n";
