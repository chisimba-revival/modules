<?php
/** Skin-based archive and speaker presentation. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarrenderer extends ChisimbaObject
{
 public function init(){$this->loadClass('webinarschedule','webinar');}
 public static function escape($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
 public function text($key){return ucfirst($this->getObject('language','language')->code2Txt('mod_webinar_'.$key,'webinar'));}
 public function button($label,$icon,$params){return '<a class="button" href="'.self::escape($this->uri($params,'webinar')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($label)).'</span></a>';}
 public function data($r){return json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);}
 public function image($url,$alt){return $url?'<img src="'.self::escape($url).'" alt="'.self::escape($alt).'" style="display:block;width:100%;height:auto;object-fit:contain" loading="lazy">':'';}
 /** Distinguish event scheduling from whether registration is enabled. */
 public function date($r)
 {
  $date=webinarschedule::start($r);if(!$date)return '';
  $upcoming=webinarschedule::isCurrent($r);$data=$this->data($r);
  $label=$this->text($upcoming?'scheduled':'presented').' '.$date->format($upcoming?'j F Y, H:i':'j F Y');
  if($upcoming){
   $label.=' '.$date->format('T');$end=webinarschedule::end($r);
   if($end&&$end>$date)$label.=' · '.sprintf($this->text('duration_display'),(int)round(($end->getTimestamp()-$date->getTimestamp())/60));
  }
  return '<p><small>'.self::escape($label).'</small></p>';
 }
 /** Render linked covers and speaker biographies through the shared native window. */
 public function cards($rows,$showRegistrationCounts=false)
 {
  $counts=$showRegistrationCounts?$this->getObject('webinarregistrations','webinar')->countsForUpcoming($rows):[];
  $html='<div class="chisimba-publication-card-grid">';$dialogs='';$seen=[];$canEdit=$this->getObject('webinareditpolicy','webinar')->canManage();
  foreach($rows as $r){
   $r['title']=html_entity_decode($r['title'],ENT_QUOTES|ENT_HTML5,'UTF-8');
   $d=$this->data($r);$url=self::escape($this->uri(['action'=>'view','id'=>$r['id']],'webinar'));
   $html.='<article class="chisimba-publication-card"><a class="chisimba-publication-card__media chisimba-publication-card__media--natural" href="'.$url.'">'.$this->image($d['image']??'',$r['title']);
   $date=webinarschedule::start($r);
   if($r['kind']==='webinar'&&$date){
    $html.='<time class="chisimba-publication-card__calendar" datetime="'.self::escape($date->format('Y-m-d')).'" aria-label="'.self::escape($date->format('j F Y')).'"><strong>'.$date->format('j').'</strong><span>'.self::escape($date->format('M')).'</span></time>';
   }
   $html.='</a><div class="chisimba-publication-card__body"><h2><a href="'.$url.'">'.self::escape($r['title']).'</a></h2>';
   $names=[];
   foreach(($d['speakers']??[]) as $speakerId){
    $speaker=$this->getObject('webinarstore')->one($speakerId);
    if(!$speaker||$speaker['kind']!=='speaker')continue;
    $speaker['title']=html_entity_decode($speaker['title'],ENT_QUOTES|ENT_HTML5,'UTF-8');
    $id='webinar-speaker-'.$speaker['id'];
    $names[]='<a href="'.self::escape($this->uri(['action'=>'view','id'=>$speaker['id']],'webinar')).'" data-ui-open="'.$id.'" aria-haspopup="dialog">'.self::escape($speaker['title']).'</a>';
    if(isset($seen[$id]))continue;
    $seen[$id]=true;$bio=$this->data($speaker);
    $composition=$this->getObject('compositionservice','contentblocks');$block=$composition->emptyBlock('text');$block['text']=$bio['description']??'';
    $window=$this->newObject('window','ui');$window->setId($id)->setTitle($speaker['title'])->setContent($composition->render([$block]).$this->button('speakerpage','user',['action'=>'view','id'=>$speaker['id']]));
    $dialogs.=$window->show();
   }
   if($names)$html.='<p class="chisimba-publication-card__byline">'.$this->getObject('iconservice','ui')->render('user',['decorative'=>true]).' '.implode(', ',$names).'</p>';
   $html.=$this->date($r);
   $hasCount=array_key_exists($r['id'],$counts);
   if($canEdit||$hasCount){
    $html.='<div class="chisimba-publication-card__actions'.($hasCount?' chisimba-publication-card__actions--summary':'').'">';
    if($hasCount)$html.='<span class="chisimba-pill" title="'.self::escape($this->text('registration_count_help')).'">'.self::escape(sprintf($this->text('registration_count'),$counts[$r['id']])).'</span>';
    if($canEdit)$html.=$this->button($r['kind']==='speaker'?'edit_speaker':'edit','pencil',['action'=>'edit','id'=>$r['id']]);
    $html.='</div>';
   }
   $html.='</div></article>';
  }
  return $html.'</div>'.$dialogs;
 }
 public function detail($r,$booking=''){$r['title']=html_entity_decode($r['title'],ENT_QUOTES|ENT_HTML5,'UTF-8');$d=$this->data($r);$html='<article class="chisimba-form-section">'.$this->image($d['banner']??$d['image']??'',$d['image_alt']??$r['title']).'<h1>'.self::escape($r['title']).'</h1>'.$this->date($r);
  $composition=$this->getObject('compositionservice','contentblocks');$block=$composition->emptyBlock('text');$block['text']=$d['description'];$html.=$composition->render([$block]);
  $html.=$this->getObject('webinarclassificationui','webinar')->detail($r);
  if($this->getObject('webinareditpolicy','webinar')->canManage())$html.=$this->button($r['kind']==='speaker'?'edit_speaker':'edit','pencil',['action'=>'edit','id'=>$r['id']]);
  $html.=$booking;
  $store=$this->getObject('webinarstore');
  if($r['kind']==='webinar'){
   $url=$d['recording']??'';
   if($url&&$this->getObject('contentmediaservice','contentblocks')->videoEmbed($url))$html.='<p><a class="button chisimba-button-primary" target="_blank" rel="noopener noreferrer" title="'.self::escape($this->text('newtab')).'" href="'.self::escape($url).'">'.$this->getObject('iconservice','ui')->render('play',['decorative'=>true]).'<span>'.self::escape($this->text('watch')).'</span></a></p>';
   elseif(($start=webinarschedule::start($r))&&$start->getTimestamp()<=time())$html.='<p>'.self::escape($this->text('missing')).'</p>';
   $speakers=[];foreach($d['speakers'] as $id){$s=$store->one($id);if($s&&$s['kind']==='speaker')$speakers[]=$s;}
   $html.='<section class="chisimba-form-section"><h2>'.self::escape($this->text('speakers')).'</h2>'.$this->cards($speakers).'</section>';
  }else{
   $rows=array_filter($store->published('webinar'),fn($w)=>in_array($r['id'],$this->data($w)['speakers'],true));
   $html.='<h2>'.self::escape($this->text('title')).'</h2>'.$this->cards($rows);
  }
  return $html.'</article>';
 }
}
