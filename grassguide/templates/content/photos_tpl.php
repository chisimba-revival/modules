<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
$d=array_merge(['creator'=>$this->getObject('user','security')->fullname(),'licence'=>'CC-BY-4.0','photo_mode'=>'add'],$grassInput??[]);
echo '<h2>'.$t('upload_photos').' — <i>'.$e($grassRecord['scientific_name']).'</i></h2>'.$r->link('back_species','arrow-left',['action'=>'edit','id'=>$grassId]);
echo '<section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><p>'.$t(!empty($grassRecord['_block_nc_images'])?'photo_blocked':'photo_allowed').'</p>';
if(!empty($grassRecord['_block_nc_images']))echo '<p>'.$t('resume_help').'</p>'.$start('resumenc',$grassId,$grassRecord['revision']).$r->button('resume_nc','refresh-cw').'</form>';
echo '</section><section class="chisimba-form-card chisimba-form-card--wide chisimba-flow"><p>'.$t('photos_help').'</p>';
echo $start('uploadphotos',$grassId,$grassInput['revision']??$grassRecord['revision']);
echo '<div class="chisimba-form-field"><label for="gg-photos">'.$t('photo_selection').'</label><input id="gg-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required data-photo-files></div><div class="chisimba-story-grid" data-photo-previews></div>';
echo $r->field('captions',$d,12000,true,true).'<p>'.$t('upload_rights').'</p><div class="chisimba-form-grid">'.$r->field('creator',$d,300,true).$r->select('licence',$d,['CC-BY-4.0','CC0-1.0','CC-BY-SA-4.0'],false,true).'</div><fieldset><legend>'.$t('photo_mode').'</legend>';
foreach(['add'=>'photo_add','replace_nc'=>'photo_replace'] as $value=>$label)echo '<label class="chisimba-choice"><input type="radio" name="photo_mode" value="'.$value.'"'.($d['photo_mode']===$value?' checked':'').'> '.$t($label).'</label>';
echo '</fieldset><p>'.$t('photo_replace_help').'</p><div class="chisimba-form-actions">'.$r->button('upload_save','upload').'</div></form></section>';
