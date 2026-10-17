<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
/** Small semantic view helpers shared with Workgroup Admin. */
class sharingview extends ChisimbaObject
{
    public function text($key) { return ucfirst(html_entity_decode($this->getObject('language','language')->code2Txt('mod_workgroup_native_'.$key,'workgroup'),ENT_QUOTES,'UTF-8')); }
    public function e($value) { return htmlspecialchars(is_scalar($value)?(string)$value:'',ENT_QUOTES,'UTF-8'); }
    public function t($key) { return $this->e($this->text($key)); }
    public function url($params=[],$module='workgroup') { return $this->e(html_entity_decode($this->uri($params,$module),ENT_QUOTES,'UTF-8')); }
    public function hidden($name,$value) { return '<input type="hidden" name="'.$this->e($name).'" value="'.$this->e($value).'">'; }
    public function link($params,$key,$icon='arrow-left',$module='workgroup') {
        return '<a class="button" href="'.$this->url($params,$module).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).' '.$this->t($key).'</a>';
    }
    public function start($key,$error='',$topic='files') {
        $html='<section class="chisimba-form-card chisimba-form-card--wide"><div class="chisimba-form-actions"><h1>'.$this->t($key).'</h1>';
        $html.=$this->getObject('contextualhelp','help')->show('workgroup',$topic).'</div>';
        if($error!=='')$html.='<p class="error" role="alert">'.$this->t('error_'.$error).'</p>';
        return $html;
    }
    public function input($name,$key,$value,$limit,$required=false) {
        return '<div class="chisimba-form-field"><label for="wg-'.$name.'">'.$this->t($key).'</label><input id="wg-'.$name.'" name="'.$name.'" maxlength="'.$limit.'" value="'.$this->e($value).'"'.($required?' required':'').'></div>';
    }
}
