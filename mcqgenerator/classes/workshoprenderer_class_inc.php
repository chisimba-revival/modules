<?php
/** Shared skin, icon and language primitives for the workshop. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class workshoprenderer extends ChisimbaObject
{
    public function text($key){return ucfirst(html_entity_decode($this->getObject('language','language')->code2Txt('mod_mcqgenerator_'.$key,'mcqgenerator'),ENT_QUOTES|ENT_HTML5,'UTF-8'));}
    public static function escape($text){return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    public function link($key,$icon,array $params){return '<a class="button chisimba-button-secondary" href="'.self::escape($this->uri($params,'mcqgenerator')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>';}
    public function button($key,$icon){return '<button class="button chisimba-button-primary" type="submit">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).($key==='generate'?'<span data-workshop-spinner style="display:none">'.$this->getObject('iconservice','ui')->render('loader-circle',['decorative'=>true]).'</span>':'').'<span>'.self::escape($this->text($key)).'</span></button>';}
    public function deleteForm(array $set,$token)
    {
        $e=[self::class,'escape'];
        return '<form '.$this->formAttributes().' method="post" data-workshop-delete data-confirm="'.$e($set['title']."\n\n".$this->text('delete_notice')).'" action="'.$e($this->uri(['action'=>'delete','id'=>$set['id']],'mcqgenerator')).'">'
            .'<input type="hidden" name="csrf_token" value="'.$e($token).'"><input type="hidden" name="version" value="'.$e($set['version']).'"><input type="hidden" name="confirm_delete" value="0">'
            .'<button disabled type="submit" class="button chisimba-button-danger">'.$this->getObject('iconservice','ui')->render('trash-2',['decorative'=>true]).'<span>'.$e($this->text('delete_set')).'</span></button></form>';
    }
    private function formAttributes()
    {
        return 'data-workshop-form data-token-url="'.self::escape($this->uri(['action'=>'formtoken'],'mcqgenerator')).'" data-session-error="'.self::escape($this->text('session_unavailable')).'"';
    }
    public function form($action,$token,$id=''){return '<form '.$this->formAttributes().' method="post" enctype="multipart/form-data"'.($action==='generate'?' data-workshop-generate data-pending="'.self::escape($this->text('generating')).'"':'').' action="'.self::escape($this->uri(['action'=>$action,'id'=>$id],'mcqgenerator')).'"><input type="hidden" name="csrf_token" value="'.self::escape($token).'">';}
}
