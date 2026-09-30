<?php
/** Reusable composition editor; its host owns form, CSRF and publishing. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class compositioneditor extends ChisimbaObject
{
    public function init() {}
    private function button($command,$label,$icon)
    {
        $s=$this->getObject('compositionservice','contentblocks');
        return '<button type="submit" name="compose_command" value="'.$s::escape($command).'" class="button chisimba-button-secondary" formnovalidate>'
            .$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.$s::escape($s->text($label)).'</span></button>';
    }
    private function field($prefix,$name,$value,$label,$media=false)
    {
        $s=$this->getObject('compositionservice','contentblocks');$id='comp_'.preg_replace('/[^a-zA-Z0-9_]/','_',$prefix.'_'.$name);
        $html='<div class="chisimba-form-field"><label for="'.$id.'">'.$s::escape($s->text($label)).'</label><input type="'.'text'.'" id="'.$id.'" name="'.$s::escape($prefix.'['.$name.']').'" value="'.$s::escape($value).'"></div>';
        if($media)$html.='<button type="button" class="button chisimba-button-secondary" data-composition-media="'.$id.'">'.$this->getObject('iconservice','ui')->render('folder-open',['decorative'=>true]).'<span>'.$s::escape($s->text('choose_media')).'</span></button>';
        return $html;
    }
    private function hiddenFields($prefix,array $values)
    {
        $s=$this->getObject('compositionservice','contentblocks');$html='';
        foreach($values as $key=>$value){$name=$prefix.'['.$key.']';
            if(is_array($value))$html.=$this->hiddenFields($name,$value);
            else $html.='<input type="hidden" name="'.$s::escape($name).'" value="'.$s::escape((string)$value).'">';
        }
        return $html;
    }
    public function palette($after='')
    {
        $s=$this->getObject('compositionservice','contentblocks');
        if($after!==''){
            $html='<details class="chisimba-composition-palette"><summary>'.$this->getObject('iconservice','ui')->render('plus',['decorative'=>true]).$s::escape($s->text('insert_after')).'</summary><div class="chisimba-form-actions">';
            foreach($s->types() as $type=>$icon)$html.=$this->button('addafter:'.$type.':'.$after,$type,$icon);
            return $html.'</div></details>';
        }
        $html='<section class="chisimba-composition-palette chisimba-guidance-card"><h2>'.$s::escape($s->text('add')).'</h2><div class="chisimba-form-actions">';
        foreach($s->types() as $type=>$icon)$html.=$this->button('add:'.$type,$type,$icon);
        return $html.'</div></section>';
    }
    public function show(array $blocks,$showPalette=true,$active='',$resume=false,array $options=[])
    {
        // Prepare native editor assets even when the canvas is empty or only contains video.
        // A text block may be inserted later without a page load.
        $this->newObject('htmlarea','htmlelements')->show();
        $s=$this->getObject('compositionservice','contentblocks');$html='<div class="chisimba-composition-editor" data-composition-canvas>';
        if(!in_array($active,array_column($blocks,'id'),true))$active=($options['openFirst']??true)?($blocks[0]['id']??''):'';
        $html.='<input type="hidden" name="composition_active" value="'.$s::escape($active).'">';
        if($resume && $active!=='')$html.='<input type="hidden" data-composition-resume value="'.$s::escape($active).'">';
        if(!$blocks)$html.='<div class="chisimba-guidance-card"><p>'.$s::escape($s->text('empty')).'</p></div>';
        foreach($blocks as $i=>$block){
            $prefix='content_blocks['.$i.']';$id=$block['id'];$type=$block['type'];$insertAfter=$showPalette?$this->palette($id):'';
            $html.='<section class="chisimba-card chisimba-composition-instance'.($id===$active?' chisimba-composition-instance--editing':'').'" tabindex="-1" data-instance="'.$s::escape($id).'"><header><h2>'.($i+1).'. '.$s::escape($s->text($type)).'</h2><div class="chisimba-form-actions"><button type="button" class="button chisimba-button-secondary" draggable="true" data-composition-drag="'.$s::escape($id).'" aria-label="'.$s::escape($s->text('drag')).'">'.$this->getObject('iconservice','ui')->render('grip-vertical',['decorative'=>true]).'<span>'.$s::escape($s->text('drag_label')).'</span></button>';
            if($i>0)$html.=$this->button('up:'.$id,'up','arrow-up');
            if($i<count($blocks)-1)$html.=$this->button('down:'.$id,'down','arrow-down');
            $html.=$this->button('duplicate:'.$id,'duplicate','copy');
            $html.=$this->button('remove:'.$id,'remove','trash-2');
            if($id!==$active)$html.=$this->button('focus:'.$id,'edit_block','pencil');
            if($id===$active && !empty($options['saveLabel']))$html.='<button type="submit" class="button chisimba-button-primary">'.$this->getObject('iconservice','ui')->render('save',['decorative'=>true]).'<span>'.$s::escape($options['saveLabel']).'</span></button><span role="status" data-composition-save-status></span>';
            $html.='</div></header>';
            if($id!==$active){
                $preview=$s->render([$block]);
                $html.='<div data-composition-preview>'.$preview.($preview===''?'<p>'.$s::escape($s->text('empty_preview')).'</p>':'').'</div>';
                $html.=$this->hiddenFields($prefix,$block).$insertAfter.'</section>';continue;
            }
            $html.='<input type="hidden" name="'.$prefix.'[id]" value="'.$s::escape($id).'"><input type="hidden" name="'.$prefix.'[type]" value="'.$s::escape($type).'">';
            if($provider=$s->provider($type)){ $html.=$provider->editBlock($prefix.'[data]',$block['data']??[]).$insertAfter.'</section>';continue; }
            $html.=$this->field($prefix,'title',$block['title'],'heading');
            if($type!=='slider' && $type!=='video' && $type!=='audio' && $type!=='video_gallery'){
                $editor=$this->newObject('htmlarea','htmlelements');$editor->name=$prefix.'[text]';$editor->cssId='comp_text_'.$id;$editor->width='100%';$editor->setContent($block['text']);
                $html.='<div class="chisimba-form-field"><label for="comp_text_'.$id.'">'.$s::escape($s->text('text')).'</label>'.$editor->show().'</div>';
            }
            if(in_array($type,['hero','reverse_hero','image_left','image_right','video','audio'],true)){
                $html.=$this->field($prefix,'url',$block['url'],$type==='video'?'video_url':($type==='audio'?'audio_url':'image_url'),true);
                if(!in_array($type,['video','audio'],true))$html.=$this->field($prefix,'alt',$block['alt'],'alt');
                $html.=$this->field($prefix,'caption',$block['caption'],'caption');
            }
            if(in_array($type,['hero','reverse_hero'],true)){
                $html.=$this->field($prefix,'button_label',$block['button_label']??'','button_label');
                $html.=$this->field($prefix,'button_url',$block['button_url']??'','button_url');
                $position=$block['button_position']??'text';$selectId='comp_position_'.$id;
                $html.='<div class="chisimba-form-field"><label for="'.$selectId.'">'.$s::escape($s->text('button_position')).'</label><select id="'.$selectId.'" name="'.$prefix.'[button_position]">';
                foreach(['text','top-left','top-right','bottom-left','bottom-right'] as $option)$html.='<option value="'.$option.'"'.($position===$option?' selected':'').'>'.$s::escape($s->text('button_position_'.str_replace('-','_',$option))).'</option>';
                $html.='</select></div><p>'.$s::escape($s->text('button_help')).'</p>';
            }
            if($type==='audio')$html.='<p class="chisimba-help-text">'.$s::escape($s->text('audio_help')).'</p>';
            if($type==='video_gallery'){
                $selectId='comp_gallery_order_'.$id;
                $html.='<div class="chisimba-form-field"><label for="'.$selectId.'">'.$s::escape($s->text('gallery_order')).'</label><select id="'.$selectId.'" name="'.$prefix.'[gallery_order]">';
                foreach(['asc'=>'oldest','desc'=>'newest'] as $value=>$label)$html.='<option value="'.$value.'"'.(($block['gallery_order']??'asc')===$value?' selected':'').'>'.$s::escape($s->text($label)).'</option>';
                $html.='</select></div><p>'.$s::escape($s->text('gallery_help')).'</p>';
                foreach(($block['videos']??[]) as $n=>$video){$vp=$prefix.'[videos]['.$n.']';
                    $html.='<fieldset><legend>'.$s::escape($s->text('video')).' '.($n+1).'</legend>'.$this->hiddenFields($vp,['id'=>$video['id']]);
                    foreach(['title'=>'heading','url'=>'video_url','thumbnail'=>'gallery_thumbnail','date'=>'gallery_date','topic'=>'gallery_topic'] as $key=>$label)$html.=$this->field($vp,$key,$video[$key]??'',$label,$key==='thumbnail');
                    $html.=$this->button('gallery_remove:'.$id.':'.$n,'gallery_remove','trash-2').'</fieldset>';
                }
                $html.=$this->button('gallery_add:'.$id,'gallery_add','plus');
            }
            if($type==='slider'){
                foreach($block['slides'] as $n=>$slide){$slidePrefix=$prefix.'[slides]['.$n.']';
                    $html.='<fieldset><legend>'.$s::escape($s->text('slide')).' '.($n+1).'</legend>'
                        .$this->field($slidePrefix,'url',$slide['url'],'image_url',true).$this->field($slidePrefix,'alt',$slide['alt'],'alt').$this->field($slidePrefix,'caption',$slide['caption'],'caption')
                        .$this->button('remove_slide:'.$id.':'.$n,'remove_slide','trash-2').'</fieldset>';
                }
                $html.=$this->button('slide:'.$id,'add_slide','plus');
            }
            $html.=$insertAfter.'</section>';
        }
        if($showPalette)$html.=$this->palette();
        $html.='</div>';
        $picker=html_entity_decode($this->uri(['action'=>'filepicker'],'filemanager'),ENT_QUOTES,'UTF-8');
        $this->appendArrayVar('headerParams','<script>window.ChisimbaCompositionPicker='.json_encode($picker,JSON_HEX_TAG|JSON_HEX_AMP).';</script><script defer src="'.$s::escape($this->getResourceUri('composition.js','contentblocks').'?v=1.026').'"></script>');
        return $html;
    }
}
