<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class grassguiderenderer extends ChisimbaObject
{
    public function init() {}
    public static function escape($v): string { return htmlspecialchars(is_scalar($v)?(string)$v:'',ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'); }
    private ?array $botanicalNames=null;
    public function names(): array
    {
        if($this->botanicalNames!==null)return $this->botanicalNames;
        $names=[];foreach($this->getObject('grassguideservice','grassguide')->all() as $row){
            $names[]=$row['scientific_name'];$names[]=$row['genus'];
            $names[]=mb_substr($row['genus'],0,1).'. '.$row['epithet'];
        }
        return $this->botanicalNames=array_values(array_unique(array_filter($names)));
    }
    public static function italic(string $text): string
    {
        return '<i>'.preg_replace('/\b(subsp\.|ssp\.|var\.|f\.) /u','</i>$1 <i>',self::escape($text)).'</i>';
    }
    public static function formatBotanical(string $text,array $names): string
    {
        usort($names,fn($a,$b)=>strlen($b)<=>strlen($a));
        $pattern=$names?'/(?<![\pL-])('.implode('|',array_map(fn($n)=>preg_quote($n,'/'),$names)).')(?![\pL-])/u':null;
        $plain=function($value)use($pattern){
            if(!$pattern)return self::escape($value);
            $parts=preg_split($pattern,$value,-1,PREG_SPLIT_DELIM_CAPTURE);$html='';
            foreach($parts as $i=>$part)$html.=$i%2?self::italic($part):self::escape($part);
            return $html;
        };
        $parts=preg_split('/\*([^*\r\n]+)\*/u',$text,-1,PREG_SPLIT_DELIM_CAPTURE);$html='';
        foreach($parts as $i=>$part)$html.=$i%2?self::italic($part):$plain($part);
        return $html;
    }
    public function licenceLabel(string $value): string { return $value==='copyright'?$this->text('copyright_label'):$value; }
    public function botanical(string $text): string { return self::formatBotanical($text,$this->names()); }
    public function text(string $key): string { return html_entity_decode($this->getObject('language','language')->code2Txt('mod_grassguide_'.$key,'grassguide'),ENT_QUOTES | ENT_HTML5,'UTF-8'); }
    public function link(string $key, string $icon, array $params): string { return '<a class="button" href="'.self::escape($this->uri($params,'grassguide')).'">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></a>'; }
    public function button(string $key, string $icon): string { return '<button class="button" type="submit">'.$this->getObject('iconservice','ui')->render($icon,['decorative'=>true]).'<span>'.self::escape($this->text($key)).'</span></button>'; }
    public function field(string $key, array $data, int $max, bool $required = false, bool $multiline = false): string
    {
        $e=[self::class,'escape']; $attributes=' id="gg-'.$key.'" name="'.$key.'" maxlength="'.$max.'"'.($required?' required':'');
        $control=$multiline?'<textarea'.$attributes.' rows="4">'.$e($data[$key] ?? '').'</textarea>':'<input type="text"'.$attributes.' value="'.$e($data[$key] ?? '').'">';
        $format='';
        if(in_array($key,['similar','habitat','ecology_source'],true)){
            $format='<div class="chisimba-stack" data-botanical-editor data-botanical-names="'.$e(json_encode($this->names(),JSON_THROW_ON_ERROR)).'"><div class="chisimba-form-actions"><button type="button" class="button" hidden data-italic-target="gg-'.$key.'">'.$this->getObject('iconservice','ui')->render('pencil',['decorative'=>true]).'<span>'.$e($this->text('italic_selection')).'</span></button></div><p class="chisimba-help-text">'.$e($this->text('italic_help')).'</p><div class="chisimba-prose" data-botanical-preview aria-label="'.$e($this->text('format_preview')).'">'.nl2br($this->botanical($data[$key]??'')).'</div></div>';
        }
        return '<div class="chisimba-form-field"><label for="gg-'.$key.'">'.$e($this->text($key)).($required?' *':'').'</label>'.$control.$format.$this->provenance($key,$data).'</div>';
    }
    public function select(string $key, array $data, array $choices, bool $optional = true, bool $literal = false, string $emptyLabel = 'unknown'): string
    {
        $e=[self::class,'escape']; $html='<div class="chisimba-form-field"><label for="gg-'.$key.'">'.$e($this->text($key)).'</label><select id="gg-'.$key.'" name="'.$key.'">';
        if ($optional) $choices=array_merge([''],$choices);
        foreach ($choices as $value) $html.='<option value="'.$e($value).'"'.(($data[$key] ?? '')===$value?' selected':'').'>'.$e($value===''?$this->text($emptyLabel):($value==='copyright'?$this->text('copyright_label'):($literal?$value:$this->text($value)))).'</option>';
        return $html.'</select></div>';
    }
    public function provenance(string $key,array $data): string
    {
        $source=$data['_field_sources'][$key] ?? null;if(!$source)return '';
        $e=[self::class,'escape'];
        return '<p class="chisimba-help-text">'.$e($this->text(!empty($data['_manual_fields'][$key])?'manual_override':'automatic_source')).' <a href="'.$e($source['url']).'" target="_blank" rel="noopener noreferrer">'.$e($source['citation']).'</a> · '.$e($source['licence']).' · '.$e($source['retrieved_at']).'</p>';
    }
}
