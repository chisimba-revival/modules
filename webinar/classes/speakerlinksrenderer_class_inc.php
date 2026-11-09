<?php
/** Accessible shared-skin speaker link presentation. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class speakerlinksrenderer extends ChisimbaObject
{
 public function init(){$this->loadClass('speakerlinks','webinar');}
 private function e($v){return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
 private function text($k){return html_entity_decode($this->getObject('webinarrenderer','webinar')->text($k),ENT_QUOTES|ENT_HTML5,'UTF-8');}
 public function publicLinks($id){
  $rows=$this->getObject('speakerlinks','webinar')->listing($id);if(!$rows)return '';$html='<nav class="chisimba-form-actions" aria-label="'.$this->e($this->text('links_title')).'">';
  foreach($rows as $row){$label=$row['label']?:$this->text('link_'.$row['category']);$html.='<a href="'.$this->e($row['url']).'" rel="noopener noreferrer">'.$this->getObject('iconservice','ui')->render(speakerlinks::categories()[$row['category']],['decorative'=>true]).' <span>'.$this->e($label).'</span></a>';}
  return $html.'</nav>';
 }
 public function rows(array $rows){
  $html='';foreach($rows as $row){$html.='<li class="chisimba-form-actions" data-link-row data-id="'.$this->e($row['id']).'" data-category="'.$this->e($row['category']).'" data-label="'.$this->e($row['label']).'" data-url="'.$this->e($row['url']).'">'.$this->getObject('iconservice','ui')->render(speakerlinks::categories()[$row['category']],['decorative'=>true]).'<a href="'.$this->e($row['url']).'">'.$this->e($row['label']?:$this->text('link_'.$row['category'])).'</a>';
   foreach(['edit'=>'pencil','delete'=>'trash-2'] as $action=>$icon)$html.='<button type="button" class="button" data-link-'.$action.' aria-label="'.$this->e($this->text('links_'.$action)).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'</button>';
   $html.='</li>';
  }return $html;
 }
 public function editor($id){
  $rows=$id?$this->getObject('speakerlinks','webinar')->listing($id):[];$csrf=$this->getObject('nativeauthwebcomposition','security')->build()['csrf']->issue('speaker-links');
  $html='<section class="chisimba-form-card chisimba-form-card--wide"><h2>'.$this->e($this->text('links_title')).'</h2><p>'.$this->e($this->text('links_help')).'</p><p data-links-save-first'.($id?' hidden':'').'>'.$this->e($this->text('links_save_first')).'</p><form method="post" data-speaker-links action="'.$this->e($this->uri(['action'=>'speaker_links'],'webinar')).'" data-confirm="'.$this->e($this->text('links_confirm')).'" data-failed="'.$this->e($this->text('editor_save_failed')).'" data-saving="'.$this->e($this->text('editor_saving')).'">';
  foreach(['id'=>$id,'csrf_token'=>$csrf,'links_version'=>speakerlinks::revision($rows),'link_id'=>bin2hex(random_bytes(16)),'operation'=>'save'] as $key=>$value)$html.='<input type="hidden" name="'.$key.'" value="'.$this->e($value).'">';
  $html.='<ul data-links-list>'.$this->rows($rows).'</ul><fieldset data-links-fields'.(!$id?' disabled':'').'><legend>'.$this->e($this->text('links_add')).'</legend><div class="chisimba-form-field"><label for="speaker-link-category">'.$this->e($this->text('links_category')).'</label><select id="speaker-link-category" name="category">';
  foreach(speakerlinks::categories() as $key=>$icon)$html.='<option value="'.$key.'">'.$this->e($this->text('link_'.$key)).'</option>';
  $html.='</select></div>';foreach(['url'=>'url','label'=>'text'] as $key=>$type)$html.='<div class="chisimba-form-field"><label for="speaker-link-'.$key.'">'.$this->e($this->text('links_'.$key)).'</label><input id="speaker-link-'.$key.'" name="'.$key.'" type="'.$type.'" maxlength="'.($key==='url'?2048:80).'"'.($key==='url'?' required':'').'></div>';
  $html.='<div class="chisimba-form-actions"><button class="button" type="submit">'.$this->getObject('iconservice','ui')->render('save',['decorative'=>true]).'<span>'.$this->e($this->text('links_save')).'</span></button><button class="button" type="button" data-link-reset>'.$this->e($this->text('links_add')).'</button></div></fieldset><p role="status" aria-live="polite" data-links-feedback></p></form></section>';
  return $html.'<script defer src="'.$this->e($this->getResourceUri('speaker-links.js','webinar')).'?v=0.028"></script>';
 }
}
