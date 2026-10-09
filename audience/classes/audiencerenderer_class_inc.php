<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class audiencerenderer extends ChisimbaObject {
 public function init(){}
 public static function escape($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
 public function text($key){return ucfirst(html_entity_decode($this->getObject('language','language')->code2Txt('mod_audience_'.$key,'audience'),ENT_QUOTES|ENT_HTML5,'UTF-8'));}
 public function link($key,$icon,$params){return '<a class="button" href="'.self::escape($this->uri($params,'audience')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>';}
 public function button($key,$icon,$extra=''){return '<button type="submit" class="button" '.$extra.'>'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></button>';}
}
