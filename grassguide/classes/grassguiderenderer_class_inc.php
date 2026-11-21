<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguiderenderer extends ChisimbaObject
{
    public function init() {}
    public static function escape($v): string { return htmlspecialchars(is_scalar($v)?(string)$v:'',ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'); }
    public function text(string $key): string { return html_entity_decode($this->getObject('language','language')->code2Txt('mod_grassguide_'.$key,'grassguide'),ENT_QUOTES | ENT_HTML5,'UTF-8'); }
    public function link(string $key, string $icon, array $params): string { return '<a class="button" href="'.self::escape($this->uri($params,'grassguide')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>'; }
    public function button(string $key, string $icon): string { return '<button class="button" type="submit">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></button>'; }
    public function field(string $key, array $data, int $max, bool $required = false, bool $multiline = false): string
    {
        $e=[self::class,'escape']; $attributes=' id="gg-'.$key.'" name="'.$key.'" maxlength="'.$max.'"'.($required?' required':'');
        $control=$multiline?'<textarea'.$attributes.' rows="4">'.$e($data[$key] ?? '').'</textarea>':'<input type="text"'.$attributes.' value="'.$e($data[$key] ?? '').'">';
        return '<div class="chisimba-form-field"><label for="gg-'.$key.'">'.$e($this->text($key)).($required?' *':'').'</label>'.$control.$this->provenance($key,$data).'</div>';
    }
    public function select(string $key, array $data, array $choices, bool $optional = true, bool $literal = false, string $emptyLabel = 'unknown'): string
    {
        $e=[self::class,'escape']; $html='<div class="chisimba-form-field"><label for="gg-'.$key.'">'.$e($this->text($key)).'</label><select id="gg-'.$key.'" name="'.$key.'">';
        if ($optional) $choices=array_merge([''],$choices);
        foreach ($choices as $value) $html.='<option value="'.$e($value).'"'.(($data[$key] ?? '')===$value?' selected':'').'>'.$e($value===''?$this->text($emptyLabel):($literal?$value:$this->text($value))).'</option>';
        return $html.'</select></div>';
    }
    public function provenance(string $key,array $data): string
    {
        $source=$data['_field_sources'][$key] ?? null;if(!$source)return '';
        $e=[self::class,'escape'];
        return '<p class="chisimba-help-text">'.$e($this->text(!empty($data['_manual_fields'][$key])?'manual_override':'automatic_source')).' <a href="'.$e($source['url']).'" target="_blank" rel="noopener noreferrer">'.$e($source['citation']).'</a> · '.$e($source['licence']).' · '.$e($source['retrieved_at']).'</p>';
    }
}
