<?php
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class helpcontent extends ChisimbaObject
{
    public function mayViewTopic($topic) {
        $service=$this->getObject('sharingservice','workgroup');
        if($topic==='files')return $service->uid()!=='';
        try{return $topic==='manage' && $service->manager($service->context());}catch(Throwable $e){return false;}
    }
    public function getTopic($topic) {
        if(!$this->mayViewTopic($topic))return null;
        $v=$this->getObject('sharingview','workgroup');
        return ['title'=>$v->text($topic==='manage'?'manage':'files'),'summary'=>$v->text($topic==='manage'?'memberhelp':'permissions'),
            'steps'=>[$v->text($topic==='manage'?'helpmanage':'helpfiles'),$v->text('helprecovery'),$v->text('removehelp')]];
    }
}
