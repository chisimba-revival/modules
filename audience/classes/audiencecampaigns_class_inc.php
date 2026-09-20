<?php
/** Reusable newsletter drafts and bounded, resumable fan-out through Communications. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audiencecampaigns extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler'){parent::init('tbl_audience_campaigns',$pearDb,$errorCallback);}
 private function store(){return $this->getObject('audienceadmin','audience');}
 private function q($v){return $this->store()->q($v);}
 public function one($id,$lock=false){if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))return null;return $this->store()->rows('SELECT * FROM tbl_audience_campaigns WHERE id='.$this->q($id).($lock?' FOR UPDATE':''))[0]??null;}
 public function progress($id){
  $this->store()->guard();$row=$this->one($id);if(!$row)throw new DomainException('invalid');
  $payload=json_decode($row['payload'],true);$total=count($payload['recipients']??[]);$counts=[];
  foreach($this->store()->rows('SELECT status,COUNT(*) n FROM tbl_communications_outbox WHERE idempotency_key LIKE '.$this->q('audience:'.$id.':%').' GROUP BY status') as $item)$counts[$item['status']]=(int)$item['n'];
  $sent=$counts['sent']??0;$pending=$counts['queued']??0;$failed=($counts['failed']??0)+($counts['cancelled']??0);
  $key=$row['state']==='draft'?'draft':($row['state']==='cancelled'?'cancelled':'mail_queued');
  if($row['state']==='dispatched'&&$pending===0)$key=$sent===$total&&$total>0?'mail_sent':'mail_attention';
  elseif($sent>0)$key='mail_sending';
  $r=$this->getObject('audiencerenderer','audience');
  return ['state'=>$row['state'],'label'=>$r->text($key),'message'=>sprintf($r->text('mail_progress'),$sent,$total,$failed),'tone'=>$key==='mail_sent'?'success':($key==='mail_attention'?'warning':'info'),'complete'=>in_array($key,['draft','cancelled','mail_sent','mail_attention'],true)];
 }
 public function listing(){$this->store()->guard();return $this->store()->rows('SELECT id,subject,state,updated_at FROM tbl_audience_campaigns ORDER BY updated_at DESC LIMIT 50');}
 public function save($id,array $input){
  $this->store()->guard();$subject=trim($input['subject']??'');$body=trim($input['body']??'');$greeting=trim($input['greeting']??'');
  if($subject===''||mb_strlen($subject)>240||strlen($body)>50000||mb_strlen($greeting)>500||preg_match('/[\r\n]/',$subject))throw new DomainException('invalid');
  $id=$id?:($input['create_id']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$id))throw new DomainException('invalid');
  $payload=['greeting'=>$greeting,'latest_recording'=>($input['latest_recording']??'')==='1','body'=>$body,'upcoming'=>($input['upcoming']??'')==='1','support'=>($input['support']??'')==='1','cursor'=>0];
  $payload['preview_html']=$this->composeHtml(['payload'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
  $payload['preview']=$this->compose(['payload'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
  $s=$this->store();$s->execute('START TRANSACTION');
  try{$old=$this->one($id,true);if($old&&($old['state']!=='draft'||(int)$old['version']!==(int)($input['version']??0)))throw new DomainException('conflict');
   if($old){if($this->update('id',$id,['subject'=>$subject,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'version'=>(int)$old['version']+1,'updated_at'=>gmdate('Y-m-d H:i:s')])===false)throw new RuntimeException('storage');}
   elseif($this->insert(['id'=>$id,'version'=>1,'subject'=>$subject,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'state'=>'draft','created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')])===false)throw new RuntimeException('storage');
   $s->execute('COMMIT');return $this->one($id);
  }catch(Throwable $e){$s->execute('ROLLBACK');throw $e;}
 }
 public function compose(array $row){
  $p=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);if(isset($p['preview']))return $p['preview'];$parts=[trim($p['greeting']??'')];
  if(!empty($p['latest_recording']))$parts[]=$this->getObject('webinarannouncements','webinar')->latestRecordingText();
  $parts[]=trim($p['body']??'');$body=implode("\n\n",array_filter($parts,static fn($part)=>$part!==''));
  if(!empty($p['upcoming']))$body.="\n\n".$this->getObject('webinarannouncements','webinar')->upcomingText($p['event_ids']??null);
  if(!empty($p['support'])){$support=$this->getObject('dbsysconfig','sysconfig')->getValue('AUDIENCE_SUPPORT_MESSAGE','audience','');if(trim((string)$support)!=='')$body.="\n\n".trim($support);}
  return trim($body);
 }
 /** Escape text before adding links; pasted HTML remains literal text. */
 public static function htmlText($text){
  $parts=preg_split('~(https?://[^\s<>]+)~u',(string)$text,-1,PREG_SPLIT_DELIM_CAPTURE);$html='';
  foreach($parts as $i=>$part){$safe=htmlspecialchars($part,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$html.=$i%2?'<a href="'.$safe.'">'.$safe.'</a>':nl2br($safe,false);}
  return $html;
 }
 public function composeHtml(array $row){
  $p=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
  if(isset($p['rendered_html']))return $p['rendered_html'];
  if(isset($p['preview_html']))return $p['preview_html'];
  // Existing frozen campaigns keep their reviewed text, including when resuming delivery.
  if(isset($p['rendered']))return '<p>'.self::htmlText($p['rendered']).'</p>';
  if(isset($p['preview']))return '<p>'.self::htmlText($p['preview']).'</p>';
  $parts=[trim($p['greeting']??'')];
  if(!empty($p['latest_recording']))$parts[]=$this->getObject('webinarannouncements','webinar')->latestRecordingText();
  $parts[]=trim($p['body']??'');$html='';
  foreach($parts as $part)if($part!=='')$html.='<p>'.self::htmlText($part).'</p>';
  if(!empty($p['upcoming']))$html.=$this->getObject('webinarannouncements','webinar')->upcomingHtml($p['event_ids']??null);
  if(!empty($p['support'])){$support=$this->getObject('dbsysconfig','sysconfig')->getValue('AUDIENCE_SUPPORT_MESSAGE','audience','');if(trim((string)$support)!=='')$html.='<p>'.self::htmlText(trim($support)).'</p>';}
  return '<div style="font-family:Arial,sans-serif;font-size:16px;line-height:1.5;max-width:600px;overflow-wrap:anywhere;word-break:break-word">'.$html.'</div>';
 }
 public function queue($id,$version){
  $this->store()->guard();$s=$this->store();$s->execute('START TRANSACTION');
  try{$r=$this->one($id,true);if(!$r)throw new DomainException('invalid');
   if($r['state']!=='draft'){$s->execute('COMMIT');return $r;}
   if((int)$r['version']!==(int)$version)throw new DomainException('conflict');
   $p=json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);$p['rendered']=$this->compose($r);$p['rendered_html']=$this->composeHtml($r);if($p['rendered']==='')throw new DomainException('invalid');
   $p['recipients']=$s->rows("SELECT id,revision FROM tbl_audience_contacts WHERE state='subscribed' ORDER BY id");$p['cursor']=0;
   $this->persist($r,$p,'queued');$s->execute('COMMIT');return $this->one($id);
  }catch(Throwable $e){$s->execute('ROLLBACK');throw $e;}
 }
 private function persist($r,$payload,$state){if($this->update('id',$r['id'],['payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'state'=>$state,'version'=>(int)$r['version']+1,'updated_at'=>gmdate('Y-m-d H:i:s')])===false)throw new RuntimeException('storage');}
 /** Called only by the CLI scheduler with a deterministic date/event key. */
 public function schedule($key,$subject,$body,array $eventIds,$expiresAt){
  if(PHP_SAPI!=='cli')throw new DomainException('forbidden');$id=substr(hash('sha256','audience:'.$key),0,32);$s=$this->store();$s->execute('START TRANSACTION');
  try{if($this->one($id,true)){$s->execute('COMMIT');return;}
   $r=['id'=>$id,'version'=>1,'state'=>'queued','subject'=>$subject,'created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')];
   $p=['greeting'=>$this->getObject('audiencerenderer','audience')->text('greeting_default'),'latest_recording'=>true,'schedule_key'=>$key,'body'=>$body,'event_ids'=>$eventIds,'upcoming'=>true,'support'=>true,'cursor'=>0,'expires_at'=>$expiresAt,'event_times'=>$this->getObject('webinarannouncements','webinar')->eventTimes($eventIds)];$r['payload']=json_encode($p);$p['rendered']=$this->compose($r);$p['rendered_html']=$this->composeHtml($r);$p['recipients']=$s->rows("SELECT id,revision FROM tbl_audience_contacts WHERE state='subscribed' ORDER BY id");$r['payload']=json_encode($p,JSON_THROW_ON_ERROR);
   if($this->insert($r)===false)throw new RuntimeException('storage');$s->execute('COMMIT');
  }catch(Throwable $e){$s->execute('ROLLBACK');throw $e;}
 }
 public function cancel($id,$version){$this->store()->guard();$s=$this->store();$s->execute('START TRANSACTION');try{$r=$this->one($id,true);if(!$r||(int)$r['version']!==(int)$version)throw new DomainException('conflict');$this->persist($r,json_decode($r['payload'],true),'cancelled');$s->execute('COMMIT');}catch(Throwable $e){$s->execute('ROLLBACK');throw $e;}}
 /** No web request performs delivery. Transactions cover a single recipient and cursor. */
 public function pump($limit=20){
  if(PHP_SAPI!=='cli')throw new DomainException('forbidden');$s=$this->store();$count=0;
  foreach($s->rows("SELECT id FROM tbl_audience_campaigns WHERE state='queued' ORDER BY created_at LIMIT 5") as $item){
   while($count<max(1,min(100,$limit))){$s->execute('START TRANSACTION');try{
    $r=$this->one($item['id'],true);if(!$r||$r['state']!=='queued'){$s->execute('COMMIT');break;}
    $p=json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);$i=(int)$p['cursor'];$recipient=$p['recipients'][$i]??null;
    if(!$recipient){$this->persist($r,$p,'dispatched');$s->execute('COMMIT');break;}
    $c=$this->getObject('audienceservice','audience')->one($recipient['id']);
    if(self::eligible($c,$recipient)){
     $key='audience:'.$r['id'].':'.$c['id'];$mail=$this->getObject('communicationservice','communications');
     if(!$mail->messageForKey($key)){
      $token=$this->getObject('audienceservice','audience')->unsubscribeToken($c['id']);$root=rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/');
      $text=self::personalise($p['rendered'],$c,html_entity_decode($this->getObject('language','language')->code2Txt('mod_audience_firstname_fallback','audience'),ENT_QUOTES|ENT_HTML5,'UTF-8'))."\n\n".$this->getObject('language','language')->code2Txt('mod_audience_unsubscribe','audience').': '.$root.'/index.php?module=audience&action=unsubscribe&token='.$token;
      $html='';
      if(isset($p['rendered_html'])){
       $fallback=html_entity_decode($this->getObject('language','language')->code2Txt('mod_audience_firstname_fallback','audience'),ENT_QUOTES|ENT_HTML5,'UTF-8');
       $first=htmlspecialchars(self::personalise('{FIRSTNAME}',$c,$fallback),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
       $html=str_replace('{FIRSTNAME}',$first,$p['rendered_html']).'<p>'.self::htmlText($this->getObject('language','language')->code2Txt('mod_audience_unsubscribe','audience').': '.$root.'/index.php?module=audience&action=unsubscribe&token='.$token).'</p>';
      }
      $result=$mail->queueEmail(['to'=>$c['email'],'toName'=>$c['name'],'subject'=>$r['subject'],'text'=>$text,'html'=>$html,'idempotencyKey'=>$key,'metadata'=>['policy_module'=>'audience','policy_class'=>'audiencecommunicationpolicy','campaign_id'=>$r['id'],'contact_id'=>$c['id'],'revision'=>(int)$c['revision']]]);
      if(empty($result['ok']))throw new RuntimeException('queue_blocked');
     }
    }
    $p['cursor']=$i+1;$this->persist($r,$p,'queued');$s->execute('COMMIT');$count++;
   }catch(Throwable $e){$s->execute('ROLLBACK');throw $e;}}
  }return ['processed'=>$count];
 }
 /** Subscriber names currently share one full-name field. Keep punctuation and
  * Unicode names intact; never derive a greeting from an email address. */
 public static function personalise($text,array $contact,$fallback='there'){
  $name=trim(preg_replace('/[\p{Z}\s\p{Cc}]+/u',' ',(string)($contact['name']??''))??'');
  $first=explode(' ',$name)[0];
  if($first===''||str_contains($name,'@')||!preg_match('/\p{L}/u',$first))$first=$fallback;
  return str_replace('{FIRSTNAME}',$first,$text);
 }
 public static function eligible($contact,$recipient){return is_array($contact)&&$contact['state']==='subscribed'&&(int)$contact['revision']===(int)$recipient['revision'];}
}
