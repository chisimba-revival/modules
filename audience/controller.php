<?php
/** Subscriber administration and newsletter composition; never sends during a page request. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audience extends controller
{
 public function init(){}
 public function requiresLogin($action=null){return $this->getParam('action','users')!=='unsubscribe';}
 private function param($key,$default=''){$v=$this->getParam($key,$default);return is_string($v)?trim($v):$default;}
 private function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
 public function dispatch($action=null){
  header('Cache-Control: private, no-store');$action=$this->param('action','users');
  if($action==='unsubscribe')return $this->unsubscribe();
  $admin=$this->getObject('audienceadmin','audience');if(!$admin->allowed()){http_response_code(403);return 'denied_tpl.php';}
  $actions=['progress','users','contact','savecontact','campaigns','compose','savecampaign','queue','cancel','settings','savesettings'];
  if(!in_array($action,$actions,true)){http_response_code(404);return 'denied_tpl.php';}
  $input=[];foreach(['name','email','state','revision','consent','evidence','subject','greeting','latest_recording','body','version','create_id','upcoming','support','support_message','enabled','hour','timezone'] as $key)$input[$key]=$this->param($key);
  $id=$this->param('id');$error='';$record=null;$campaigns=$this->getObject('audiencecampaigns','audience');$write=in_array($action,['savecontact','savecampaign','queue','cancel','savesettings'],true);
  if($action==='progress'){header('Content-Type: application/json; charset=UTF-8');try{echo json_encode($campaigns->progress($id),JSON_THROW_ON_ERROR);}catch(DomainException $e){http_response_code(404);echo '{}';}exit;}
  if($write){
   if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')$error='invalid';
   elseif(!$this->csrf()->consume('audience-admin',$this->param('csrf_token')))$error='expired';
   else try{
    if($action==='savecontact')$record=$admin->save($id,$input);
    elseif($action==='savecampaign')$record=$campaigns->save($id,$input);
    elseif($action==='queue')$record=$campaigns->queue($id,$input['version']);
    elseif($action==='cancel'){$campaigns->cancel($id,$input['version']);$record=$campaigns->one($id);}
    else $this->saveSettings($input);
   }catch(DomainException $e){$error=in_array($e->getMessage(),['invalid','conflict','duplicate','consent_required','forbidden'],true)?$e->getMessage():'failed';}
   catch(Throwable $e){$error='failed';}
  }
  $token=$this->csrf()->issue('audience-admin');
  if($write&&$this->param('ajax')==='1'){
   if($error!=='')http_response_code(422);header('Content-Type: application/json; charset=UTF-8');
   $r=$this->getObject('audiencerenderer','audience');$data=['ok'=>$error==='','csrf'=>$token,'message'=>$r->text($error?:'saved')];
   if($record){$data['id']=$record['id'];$data['version']=$record['version']??$record['revision'];$data['state']=$record['state'];$data['stateLabel']=$r->text($record['state']);if(isset($record['subject'])){$p=json_decode($record['payload'],true);$data['preview']=$p['rendered']??$campaigns->compose($record);$data['previewHtml']=$campaigns->composeHtml($record);$data['count']=count($p['recipients']??[]);if($action==='queue'&&$error==='')$data['message']=sprintf($r->text('queued_count'),$data['count']);if($action==='cancel'&&$error==='')$data['message']=$r->text('cancelled');}}
   echo json_encode($data,JSON_THROW_ON_ERROR);exit;
  }
  $view=['savecontact'=>'contact','savecampaign'=>'compose','queue'=>'compose','cancel'=>'compose','savesettings'=>'settings'][$action]??$action;
  if(in_array($view,['contact','compose'],true)&&$id!==''&&!$record)$record=$view==='contact'?$this->getObject('audienceservice','audience')->one($id):$campaigns->one($id);
  if(in_array($view,['contact','compose'],true)&&$id!==''&&!$record){http_response_code(404);return 'denied_tpl.php';}
  $this->setVar('audienceView',$view);$this->setVar('audienceRecord',$record);$this->setVar('audienceInput',$write&&$error!==''?$input:null);$this->setVar('audienceError',$error);$this->setVar('audienceCsrf',$token);
  return 'admin_tpl.php';
 }
 private function saveSettings($input){
  $hour=filter_var($input['hour'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>23]]);
  if($hour===false||!in_array($input['timezone'],DateTimeZone::listIdentifiers(),true)||mb_strlen($input['support_message'])>10000)throw new DomainException('invalid');
  $cfg=$this->getObject('dbsysconfig','sysconfig');$enabled=$input['enabled']==='1';$was=$cfg->getValue('WEBINAR_ANNOUNCEMENTS_ENABLED','webinar')==='TRUE';
  $cfg->changeParam('AUDIENCE_SUPPORT_MESSAGE','audience',$input['support_message']);$cfg->changeParam('WEBINAR_ANNOUNCEMENTS_HOUR','webinar',(string)$hour);$cfg->changeParam('WEBINAR_ANNOUNCEMENTS_TIMEZONE','webinar',$input['timezone']);
  if($enabled&&!$was)$cfg->changeParam('WEBINAR_ANNOUNCEMENTS_SINCE','webinar',(string)time());
  $cfg->changeParam('WEBINAR_ANNOUNCEMENTS_ENABLED','webinar',$enabled?'TRUE':'FALSE');$cfg->setProperties('webinar');$cfg->setProperties('audience');
 }
 private function unsubscribe(){
  header('Referrer-Policy: no-referrer');$s=$this->getObject('audienceservice','audience');$token=$this->param('token');$valid=$s->contactForToken($token);$done=false;$error='';
  if(!$valid)$error='invalid';elseif(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
   if(!$this->csrf()->consume('audience-unsubscribe',$this->param('csrf_token')))$error='expired';else $done=$s->unsubscribe($token);
  }
  $this->setVar('audienceUnsubscribeToken',$token);$this->setVar('audienceUnsubscribed',$done);$this->setVar('audienceError',$error);$this->setVar('audienceCsrf',$this->csrf()->issue('audience-unsubscribe'));return 'unsubscribe_tpl.php';
 }
}
