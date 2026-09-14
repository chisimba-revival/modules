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
 public function date($r){$d=$this->data($r);if(empty($r['presented_at']))return '';try{$date=new DateTimeImmutable($r['presented_at'],new DateTimeZone($d['timezone']));}catch(Throwable $e){return ''; }return '<p><small>'.self::escape($this->text(webinarschedule::canRegister($r)?'scheduled':'presented').' '.$date->format(webinarschedule::canRegister($r)?'j F Y, H:i T':'j F Y')).'</small></p>';}
 public function cards($rows){$html='<div class="chisimba-publication-card-grid">';foreach($rows as $r){$d=$this->data($r);$html.='<article class="chisimba-publication-card"><div class="chisimba-publication-card__media chisimba-publication-card__media--natural">'.$this->image($d['image']??'',$r['title']).'</div><div class="chisimba-publication-card__body"><h2><a href="'.self::escape($this->uri(['action'=>'view','id'=>$r['id']],'webinar')).'">'.self::escape($r['title']).'</a></h2>'.$this->date($r).'</div></article>';}return $html.'</div>';}
 public function detail($r){$d=$this->data($r);$html='<article class="chisimba-form-section">'.$this->image($d['banner']??$d['image']??'',$r['title']).'<h1>'.self::escape($r['title']).'</h1>'.$this->date($r);
  $composition=$this->getObject('compositionservice','contentblocks');$block=$composition->emptyBlock('text');$block['text']=$d['description'];$html.=$composition->render([$block]);
  if(webinarschedule::canRegister($r))$html.='<div class="chisimba-form-actions">'.$this->button('register','calendar-check',['action'=>'register','id'=>$r['id']]).'</div>';
  $store=$this->getObject('webinarstore');
  if($r['kind']==='webinar'){
   $url=$d['recording']??'';
   if($url&&$this->getObject('contentmediaservice','contentblocks')->videoEmbed($url))$html.='<p><a class="button chisimba-button-primary" target="_blank" rel="noopener noreferrer" title="'.self::escape($this->text('newtab')).'" href="'.self::escape($url).'">'.$this->getObject('iconservice','ui')->render('play',['decorative'=>true]).'<span>'.self::escape($this->text('watch')).'</span></a></p>';
   elseif(!webinarschedule::canRegister($r))$html.='<p>'.self::escape($this->text('missing')).'</p>';
   $speakers=[];foreach($d['speakers'] as $id){$s=$store->one($id);if($s&&$s['kind']==='speaker')$speakers[]=$s;}
   $html.='<h2>'.self::escape($this->text('speakers')).'</h2>'.$this->cards($speakers);
  }else{
   $rows=array_filter($store->published('webinar'),fn($w)=>in_array($r['id'],$this->data($w)['speakers'],true));
   $html.='<h2>'.self::escape($this->text('title')).'</h2>'.$this->cards($rows);
  }
  return $html.'</article>';
 }
}
