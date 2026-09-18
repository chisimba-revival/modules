<?php
/** Shared skin, icon and language primitives for the workshop. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class workshoprenderer extends ChisimbaObject
{
    public function text($key){return ucfirst(html_entity_decode($this->getObject('language','language')->code2Txt('mod_questionworkshop_'.$key,'questionworkshop'),ENT_QUOTES|ENT_HTML5,'UTF-8'));}
    public static function escape($text){return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    public function link($key,$icon,array $params){return '<a class="button chisimba-button-secondary" href="'.self::escape($this->uri($params,'questionworkshop')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>';}
    public function button($key,$icon){return '<button class="button chisimba-button-primary" type="submit">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></button>';}
    public function form($action,$token,$id=''){return '<form method="post" enctype="multipart/form-data" action="'.self::escape($this->uri(['action'=>$action,'id'=>$id],'questionworkshop')).'"><input type="hidden" name="csrf_token" value="'.self::escape($token).'">';}
}
