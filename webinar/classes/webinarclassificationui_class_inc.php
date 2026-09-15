<?php
/** Public term choices are derived only from readable webinars. @author Derek Keats */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class webinarclassificationui extends ChisimbaObject
{
    public function init(){}
    public function terms($id,$kind){return $this->getObject('webinareditservice','webinar')->classification()->forItem('webinar',$id,$kind);}
    public function catalogue(array $rows,$action,$category,$tag)
    {
        $choices=['category'=>[],'tag'=>[]];$result=[];
        foreach($rows as $row){$matches=true;foreach(['category'=>$category,'tag'=>$tag] as $kind=>$selected){$terms=$this->terms($row['id'],$kind);foreach($terms as $term)$choices[$kind][$term['id']]=$term['name'];if($selected!==''&&!in_array($selected,array_column($terms,'id'),true))$matches=false;}if($matches)$result[]=$row;}
        $r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];
        $html='<form class="chisimba-form chisimba-form-actions" method="get"><input type="hidden" name="module" value="webinar"><input type="hidden" name="action" value="'.$e($action).'">';
        foreach(['category'=>$category,'tag'=>$tag] as $kind=>$selected){asort($choices[$kind],SORT_NATURAL|SORT_FLAG_CASE);$html.='<div class="chisimba-form-field"><label for="webinar-filter-'.$kind.'">'.$e($r->text($kind==='category'?'categories':'tags')).'</label><select id="webinar-filter-'.$kind.'" name="'.$kind.'"><option value="">'.$e($r->text('filter_all')).'</option>';foreach($choices[$kind] as $id=>$name)$html.='<option value="'.$e($id).'"'.($selected===$id?' selected':'').'>'.$e($name).'</option>';$html.='</select></div>';}
        $html.='<button class="button" type="submit">'.$this->getObject('iconservice','ui')->render('filter',['decorative'=>true]).'<span>'.$e($r->text('filter')).'</span></button>'.$r->button('filter_reset','rotate-ccw',['action'=>$action]).'</form>';
        return [$result,$html];
    }
    public function detail($record)
    {
        if($record['kind']!=='webinar'||$record['status']!=='published')return '';
        $r=$this->getObject('webinarrenderer','webinar');$e=['webinarrenderer','escape'];$html='';$action=webinarschedule::isCurrent($record)?'upcoming':'archive';
        foreach(['category'=>'categories','tag'=>'tags'] as $kind=>$label){$terms=$this->terms($record['id'],$kind);if(!$terms)continue;$links=[];foreach($terms as $term)$links[]='<a href="'.$e($this->uri(['action'=>$action,$kind=>$term['id']],'webinar')).'">'.$e($term['name']).'</a>';$html.='<p><strong>'.$e($r->text($label)).':</strong> '.implode(', ',$links).'</p>';}
        return $html;
    }
}
