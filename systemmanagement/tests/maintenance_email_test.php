<?php
/**
 * Behaviour regressions for unplanned maintenance, draft retention and queue outcomes.
 * No database, transport or email delivery is used by these tests.
 * @author Derek Keats
 * @package systemmanagement
 */
$GLOBALS['kewl_entry_point_run']=true;
function log_debug($message) {}
class ChisimbaObject
{
    public function getObject($name,$module=null){return $GLOBALS['maintenanceTestLanguage'];}
}
class controller extends ChisimbaObject
{
    public $parameters=array();public $variables=array();
    public function getParam($name,$default=''){return $this->parameters[$name]??$default;}
    public function setVar($name,$value){$this->variables[$name]=$value;}
    public function uri($parameters,$module){return '/index.php?module='.$module.'&'.http_build_query($parameters);}
    public function getObject($name,$module=null){if($name==='contextualhelp')return new class{function show($module,$topic){return '';}};if($name==='iconservice')return new class{function render($name,$options){return '<span aria-hidden="true"></span>';}};return parent::getObject($name,$module);}
    public function render(){extract($this->variables);ob_start();include dirname(__DIR__).'/templates/content/dashboard_tpl.php';return ob_get_clean();}
}
$GLOBALS['maintenanceTestLanguage']=new class {
    private $text=array();
    function __construct(){foreach(file(dirname(__DIR__).'/register.conf') as $line)if(str_starts_with($line,'TEXT: ')){[$key,$description,$value]=explode('|',trim(substr($line,6)),3);$this->text[$key]=$value;}}
    function code2Txt($key,$module){if(!isset($this->text[$key]))throw new RuntimeException('Unregistered language key: '.$key);return $this->text[$key];}
};
require dirname(__DIR__).'/classes/systemmanagementmailer_class_inc.php';
require dirname(__DIR__).'/controller.php';
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
function inject($object,$property,$value){(new ReflectionProperty($object,$property))->setValue($object,$value);}
function fixture($emails=array('first@example.test','second@example.test'))
{
    $clock=new class {public $formatted=array();function formatDateTime($value){$this->formatted[]=$value;return 'formatted '.$value;}function storageToLocal($value){return $value;}};
    $queue=new class {public $items=array();public $failAt=null;public $throwAt=null;function queueEmail($input){$attempt=count($this->items);if($this->throwAt===$attempt)throw new RuntimeException('Simulated uncertain queue exception');if($this->failAt===$attempt)return array('ok'=>false,'code'=>'queue_failed');$this->items[]=$input;return array('ok'=>true);}};
    $recipients=new class($emails){private $emails;function __construct($emails){$this->emails=$emails;}function recipientEmails($audience){return $this->emails;}function recentEmailDeliveries($limit){return array();}};
    $mailer=new systemmanagementmailer();
    inject($mailer,'clock',$clock);inject($mailer,'communications',$queue);inject($mailer,'recipients',$recipients);
    inject($mailer,'site',new class{function getSiteName(){return 'Test site';}});
    inject($mailer,'user',new class{function userId(){return 'test-admin';}});
    inject($mailer,'config',new class{function getValue($name,$module){return array('COMMUNICATION_TRANSPORT'=>'sendgrid','COMMUNICATION_FROM_EMAIL'=>'sender@example.test','COMMUNICATION_SENDGRID_API_KEY'=>'test-only-never-used')[$name]??'';}});
    return array($mailer,$queue,$clock);
}
function consoleFixture($mailer,array $maintenance=array(),$validCsrf=true)
{
    $console=new systemmanagement();
    inject($console,'mailer',$mailer);
    inject($console,'service',new class($maintenance){private $plan;function __construct($plan){$this->plan=array_merge(array('start'=>'','end'=>'','active'=>false,'message'=>'Existing offline notice'),$plan);}function maintenance(){return $this->plan;}function storageToLocal($value){return $value;}});
    inject($console,'user',new class{function isAdmin(){return true;}});
    inject($console,'csrf',new class($validCsrf){private $valid;function __construct($valid){$this->valid=$valid;}function consume($context,$token){return $this->valid;}function issue($context){return 'fresh-token';}});
    inject($console,'clock',new stdClass());
    $console->parameters=array('audience'=>'lecturers','subject'=>'Emergency upgrade','email_message'=>"Keep this draft.\nSecond line.");
    $_SERVER['REQUEST_METHOD']='POST';return $console;
}
foreach(array(array(),array('start'=>'','end'=>''),array('start'=>'2026-09-09 10:00:00','end'=>''),array('start'=>'','end'=>'2026-09-09 11:00:00')) as $plan){
    [$mailer,$queue,$clock]=fixture();$result=$mailer->send('everyone','Emergency upgrade','My exact message',$plan);
    check($result['ok']&&$result['count']===2&&$queue->items[0]['text']==="My exact message\n\nUnplanned maintenance\n\nTest site"&&$clock->formatted===array(),'missing or partial dates queue unplanned notice without formatting empty dates');
}
[$mailer,$queue,$clock]=fixture();$result=$mailer->send('everyone','Planned upgrade','Message',array('start'=>'2026-09-09 10:00:00','end'=>'2026-09-09 11:00:00'));
check($result['ok']&&count($clock->formatted)===2&&str_contains($queue->items[0]['text'],"Planned maintenance window:\nStart: formatted 2026-09-09 10:00:00\nEnd: formatted 2026-09-09 11:00:00"),'complete saved plan retains both formatted dates');
check(count(array_unique(array_column($queue->items,'idempotencyKey')))===2&&$queue->items[0]['metadata']['batch_id']===$result['batchId'],'one auditable queue item per recipient in the same batch');
[$mailer,$queue]=fixture();$console=consoleFixture($mailer);$console->dispatch('sendmaintenanceemail');
check(count($queue->items)===2&&str_contains($console->variables['systemMessage'],'queued: 2')&&str_contains($console->variables['systemMessage'],'Delivery is not yet confirmed'),'controller allows unscheduled notice and distinguishes queued from delivered');
check($console->variables['systemEmailDraft']['message']===$console->parameters['email_message'],'successful submission retains message for reference');
foreach(array('csrf','required','length','empty_audience','queue_failure','partial_failure','uncertain') as $case){
    [$mailer,$queue]=fixture($case==='empty_audience'?array():array('first@example.test','second@example.test'));
    $console=consoleFixture($mailer,array(),$case!=='csrf');
    $console->parameters['email_message']="  Preserve \"quotes\" & markup </textarea><script>bad()</script>\nSecond line.  ";
    if($case==='required')$console->parameters['subject']='   ';
    if($case==='length')$console->parameters['subject']=str_repeat('x',256);
    if($case==='queue_failure')$queue->failAt=0;
    if($case==='partial_failure')$queue->failAt=1;
    if($case==='uncertain')$queue->throwAt=1;
    $console->dispatch('sendmaintenanceemail');
    $draft=$console->variables['systemEmailDraft'];$error=$console->variables['systemError'];
    check($draft===array('audience'=>'lecturers','subject'=>$console->parameters['subject'],'message'=>$console->parameters['email_message'])&&$error!=='',$case.' preserves audience, exact subject and multiline message');
    $html=$console->render();
    check(str_contains($html,'&lt;/textarea&gt;&lt;script&gt;')&&!str_contains($html,'<script>bad()')&&str_contains($html,'value="lecturers" selected'),'retained draft is escaped safely and audience remains selected');
    if(in_array($case,array('partial_failure','uncertain'),true))check(count($queue->items)===1&&str_contains($error,'Confirmed queued: 1 of 2')&&str_contains($error,'before retrying'),'partial or uncertain result reports confirmed count and avoids claiming nothing queued');
    else check(count($queue->items)===0&&(str_contains(strtolower($error),'did not queue')||str_contains($error,'No maintenance email was queued')),'rejected submission explicitly reports no mail queued');
}
