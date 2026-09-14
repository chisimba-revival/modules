<?php
/** Skin-based archive and speaker presentation. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarrenderer extends ChisimbaObject
{
 public function init(){$this->loadClass('webinarschedule','webinar');}
 public static function escape($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
 public function text($key){return $this->getObject('language','language')->code2Txt('mod_webinar_'.$key,'webinar');}
 public function button($label,$icon,$params){return '<a class="button" href="'.self::escape($this->uri($params,'webinar')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($label)).'</span></a>';}
 public function data($r){return json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);}
 public function image($url,$alt){return $url?'<img src="'.self::escape($url).'" alt="'.self::escape($alt).'" style="display:block;width:100%;height:auto;object-fit:contain" loading="lazy">':'';}
 /** Distinguish event scheduling from whether registration is enabled. */
 public function date($r)
 {
  $date=webinarschedule::start($r);if(!$date)return '';
  $upcoming=$date->getTimestamp()>time();$data=$this->data($r);
  $label=$this->text($upcoming?'scheduled':'presented').' '.$date->format($upcoming?'j F Y, H:i':'j F Y');
  if($upcoming){
   if(!empty($data['ends_at'])){
    try{$end=new DateTimeImmutable($data['ends_at'],$date->getTimezone());$label.='–'.$end->format($end->format('Y-m-d')===$date->format('Y-m-d')?'H:i':'j F Y, H:i');}catch(Throwable $e){}
   }
   $label.=' '.$date->format('T');
  }
  return '<p><small>'.self::escape($label).'</small></p>';
 }
 /** Render linked covers and speaker biographies through the shared native window. */
 public function cards($rows)
 {
  $html='<div class="chisimba-publication-card-grid">';$dialogs='';$seen=[];
  foreach($rows as $r){
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
    $id='webinar-speaker-'.$speaker['id'];
    $names[]='<a href="'.self::escape($this->uri(['action'=>'view','id'=>$speaker['id']],'webinar')).'" data-ui-open="'.$id.'" aria-haspopup="dialog">'.self::escape($speaker['title']).'</a>';
    if(isset($seen[$id]))continue;
    $seen[$id]=true;$bio=$this->data($speaker);
    $composition=$this->getObject('compositionservice','contentblocks');$block=$composition->emptyBlock('text');$block['text']=$bio['description']??'';
    $window=$this->newObject('window','ui');$window->setId($id)->setTitle($speaker['title'])->setContent($composition->render([$block]).$this->button('speakerpage','user',['action'=>'view','id'=>$speaker['id']]));
    $dialogs.=$window->show();
   }
   if($names)$html.='<p class="chisimba-publication-card__byline">'.$this->getObject('iconservice','ui')->render('user',['decorative'=>true]).' '.implode(', ',$names).'</p>';
   $html.=$this->date($r).'</div></article>';
  }
  return $html.'</div>'.$dialogs;
 }
 public function detail($r){$d=$this->data($r);$html='<article class="chisimba-form-section">'.$this->image($d['banner']??$d['image']??'',$r['title']).'<h1>'.self::escape($r['title']).'</h1>'.$this->date($r);
  $composition=$this->getObject('compositionservice','contentblocks');$block=$composition->emptyBlock('text');$block['text']=$d['description'];$html.=$composition->render([$block]);
  if(webinarschedule::canRegister($r))$html.='<div class="chisimba-form-actions">'.$this->button('register','calendar-check',['action'=>'register','id'=>$r['id']]).'</div>';
  $store=$this->getObject('webinarstore');
  if($r['kind']==='webinar'){
   $url=$d['recording']??'';
   if($url&&$this->getObject('contentmediaservice','contentblocks')->videoEmbed($url))$html.='<p><a class="button chisimba-button-primary" target="_blank" rel="noopener noreferrer" title="'.self::escape($this->text('newtab')).'" href="'.self::escape($url).'">'.$this->getObject('iconservice','ui')->render('play',['decorative'=>true]).'<span>'.self::escape($this->text('watch')).'</span></a></p>';
   elseif(($start=webinarschedule::start($r))&&$start->getTimestamp()<=time())$html.='<p>'.self::escape($this->text('missing')).'</p>';
   $speakers=[];foreach($d['speakers'] as $id){$s=$store->one($id);if($s&&$s['kind']==='speaker')$speakers[]=$s;}
   $html.='<h2>'.self::escape($this->text('speakers')).'</h2>'.$this->cards($speakers);
  }else{
   $rows=array_filter($store->published('webinar'),fn($w)=>in_array($r['id'],$this->data($w)['speakers'],true));
   $html.='<h2>'.self::escape($this->text('title')).'</h2>'.$this->cards($rows);
  }
  return $html.'</article>';
 }
}
