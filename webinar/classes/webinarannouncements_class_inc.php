<?php
/** Webinar-owned content and calendar policy for shared Audience campaigns. */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarannouncements extends ChisimbaObject
{
 public function init(){$this->loadClass('webinarschedule','webinar');}
 /** One public list supplies both email alternatives. */
 private function upcomingItems($ids=null){
  $items=[];$root=rtrim($this->getObject('altconfig','config')->getSiteRoot(),'/');
  foreach(webinarschedule::catalogue($this->getObject('webinarstore','webinar')->published('webinar'),false) as $record){
   if(!webinarschedule::canRegister($record)||($ids!==null&&!in_array($record['id'],$ids,true)))continue;
   $items[]=['record'=>$record,'title'=>html_entity_decode($record['title'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'date'=>webinarschedule::start($record)->format('j F Y, H:i T'),'url'=>$root.'/index.php?module=webinar&action=view&id='.rawurlencode($record['id'])];
  }return $items;
 }
 public function upcomingText($ids=null){
  $r=$this->getObject('webinarrenderer','webinar');$parts=[];
  foreach($this->upcomingItems($ids) as $item)$parts[]=$r->text('email_title').' '.$item['title']."\n".$r->text('email_datetime').' '.$item['date']."\n".$r->text('email_register').' '.$item['url'];
  return implode("\n\n",$parts);
 }
 /** Email markup is self-contained; mail clients do not load the site's skin. */
 public function upcomingHtml($ids=null){
  $r=$this->getObject('webinarrenderer','webinar');$e=static fn($v)=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$html='';
  foreach($this->upcomingItems($ids) as $item){
   $html.='<section>';$image=$this->getObject('webinaremailimage','webinar')->thumbnail($item['record']);
   if($image)$html.='<p><img src="'.$e($image['url']).'" width="'.$image['width'].'" height="'.$image['height'].'" alt="'.$e($item['title']).'" style="max-width:100%;height:auto"></p>';
   $html.='<p><strong>'.$e($r->text('email_title')).'</strong> '.$e($item['title']).'<br><strong>'.$e($r->text('email_datetime')).'</strong> '.$e($item['date']).'<br><strong>'.$e($r->text('email_register')).'</strong> <a href="'.$e($item['url']).'">'.$e($item['url']).'</a></p></section>';
  }return $html;
 }
 /** Select the latest completed public webinar, never an older fallback. */
 public static function latestCompleted(array $records,$now){
  foreach(webinarschedule::catalogue($records,true,$now) as $record){
   $data=json_decode($record['payload'],true);
   if(empty($data['cancelled']))return $record;
  }return null;
 }
 public function latestRecordingText(){
  $record=self::latestCompleted($this->getObject('webinarstore','webinar')->published('webinar'),time());
  if(!$record)return '';
  $data=json_decode($record['payload'],true);$url=trim((string)($data['recording']??''));
  if(!in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['https','http'],true))return '';
  $embed=$this->getObject('contentmediaservice','contentblocks')->videoEmbed($url);
  if(!is_string($embed)||!preg_match('~^https://www\.youtube-nocookie\.com/embed/([a-zA-Z0-9_-]{6,20})$~D',$embed,$match))return '';
  return $this->getObject('webinarrenderer','webinar')->text('latest_recording')."\n".html_entity_decode($record['title'],ENT_QUOTES|ENT_HTML5,'UTF-8')."\nhttps://www.youtube.com/watch?v=".$match[1];
 }
 /** Every Monday's general newsletter contains all currently bookable webinars.
  * The newsletter follows one site time zone, independently of event dates.
  * Activation never catches up announcements from before it was enabled.
  */
 public static function plan(array $records,$now,$enabledAt,$hour=8,$timezone='Africa/Johannesburg')
 {
  $local=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone($timezone));
  $due=$local->modify('monday this week')->setTime($hour,0)->getTimestamp();
  if($due<$enabledAt||$due>$now||$now-$due>=86400)return [];
  $ids=[];
  foreach(webinarschedule::catalogue($records,false,$now) as $record){
   if(webinarschedule::canRegister($record,$now))$ids[]=$record['id'];
  }
  if(!$ids)return [];
  return [['key'=>'weekly:'.$timezone.':'.$local->modify('monday this week')->format('Y-m-d'),
   'kind'=>'weekly','due'=>$due,'ids'=>$ids]];
 }
 public function eventTimes(array $ids){$times=[];foreach($ids as $id){$r=$this->getObject('webinarstore','webinar')->one($id);if($r&&webinarschedule::canRegister($r))$times[$id]=webinarschedule::start($r)->getTimestamp();}return $times;}
 public function stillCurrent(array $payload){
  if(!str_starts_with($payload['schedule_key']??'','weekly:')||time()>=(int)($payload['expires_at']??0))return false;
  $times=$payload['event_times']??[];if(!$times)return false;
  return $this->eventTimes(array_keys($times))===$times;
 }
 public function run(){
  if(PHP_SAPI!=='cli')throw new DomainException('forbidden');$cfg=$this->getObject('dbsysconfig','sysconfig');
  if($cfg->getValue('WEBINAR_ANNOUNCEMENTS_ENABLED','webinar')!=='TRUE')return ['scheduled'=>0];
  $enabled=(int)$cfg->getValue('WEBINAR_ANNOUNCEMENTS_SINCE','webinar',0);if(!$enabled)return ['scheduled'=>0];
  $plans=self::plan($this->getObject('webinarstore','webinar')->published('webinar'),time(),$enabled,(int)$cfg->getValue('WEBINAR_ANNOUNCEMENTS_HOUR','webinar',8),(string)$cfg->getValue('WEBINAR_ANNOUNCEMENTS_TIMEZONE','webinar','Africa/Johannesburg'));
  $r=$this->getObject('webinarrenderer','webinar');$site=$this->getObject('altconfig','config')->getSiteName();
  foreach($plans as $p){$subject=sprintf($r->text('announcement_'.$p['kind']),$site);$this->getObject('audiencecampaigns','audience')->schedule($p['key'],$subject,$subject,$p['ids'],$p['due']+86400);}
  return ['scheduled'=>count($plans)];
 }
}
