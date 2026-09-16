<?php
/** Semantic forms and existing skin primitives; no module-owned visual theme. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class spokenrenderer extends ChisimbaObject
{
    public function text($key) { return ucfirst($this->getObject('language','language')->code2Txt('mod_spokenassessment_'.$key,'spokenassessment')); }
    public static function escape($value) { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    public function link($key, $icon, array $params)
    { return '<a class="button chisimba-button-secondary" href="'.self::escape($this->uri($params,'spokenassessment')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>'; }
    public function submit($key, $icon)
    { return '<button class="button chisimba-button-primary" type="submit">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></button>'; }
    public function form($action, $id, $token, $context, $upload = false)
    {
        return '<form method="post" action="'.self::escape($this->uri(['action'=>$action,'id'=>$id],'spokenassessment')).'"'.($upload?' enctype="multipart/form-data" data-spoken-recorder':'').'>'
            .'<input type="hidden" name="csrf_token" value="'.self::escape($token).'"><input type="hidden" name="contextcode" value="'.self::escape($context).'">';
    }
    public function textarea($name, $key, $value = '', $max = 10000, $required = false)
    {
        return '<div class="chisimba-form-field"><label for="spoken_'.$name.'">'.self::escape($this->text($key)).'</label><textarea id="spoken_'.$name.'" name="'.$name.'" rows="6" maxlength="'.$max.'"'.($required?' required':'').'>'.self::escape($value).'</textarea></div>';
    }
    public function date($value)
    { return self::escape($this->getObject('timeanddateservice','timeanddate-service')->formatDateTime($value)); }
}
