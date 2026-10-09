<?php
$GLOBALS['kewl_entry_point_run']=true;
class dbTable{public function getObject($n,$m){return $GLOBALS['services'][$n];}}
require __DIR__.'/../classes/audiencecampaigns_class_inc.php';
$GLOBALS['services']=['dbsysconfig'=>new class{public $heading='Message from the LTB team';function getValue($k,$m,$d=''){return $this->heading;}},'audiencerenderer'=>new class{function text($k){return 'Message from the team';}},'webinarannouncements'=>new class{function latestRecordingText(){return 'Recording';}function latestRecordingHtml(){return '<h2>Recording</h2>';}}];
$c=new audiencecampaigns;function check($v,$m){if(!$v)throw new RuntimeException($m);}
foreach([''," \n\t"] as $body){$r=['payload'=>json_encode(['body'=>$body,'latest_recording'=>true])];check(!str_contains($c->compose($r),'Message from'),'Empty plain heading');check(!str_contains($c->composeHtml($r),'Message from'),'Empty HTML heading');}
$r=['payload'=>json_encode(['body'=>'Hello <team>','latest_recording'=>true])];$html=$c->composeHtml($r);check(str_contains($html,'<h2>Message from the LTB team</h2><p>Hello &lt;team&gt;</p>'),'HTML heading and escaping');check(strpos($html,'Recording')<strpos($html,'Message from'),'Recording precedes message');check(str_contains($c->compose($r),"Recording\n\nMessage from the LTB team\n\nHello <team>"),'Plain text heading');
$GLOBALS['services']['dbsysconfig']->heading='';check(str_contains($c->composeHtml($r),'Message from the team'),'Shared fallback');
$GLOBALS['services']['dbsysconfig']->heading='<script>unsafe</script>';check(str_contains($c->composeHtml($r),'&lt;script&gt;unsafe&lt;/script&gt;'),'Heading escaping');
$r=['payload'=>json_encode(['body'=>'Updated','preview'=>'Frozen','preview_html'=>'<p>Frozen</p>'])];check($c->compose($r)==='Frozen'&&$c->composeHtml($r)==='<p>Frozen</p>','Reviewed previews preserved');
echo "PASS: optional message heading, section order, plain/HTML escaping, shared fallback and frozen previews.\n";
