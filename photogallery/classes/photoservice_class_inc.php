<?php
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class photoservice extends ChisimbaObject
{
    public function init(){}
    public function canManage(){ $u=$this->getObject('user','security');return $u->isLoggedIn()&&$u->isAdmin(); }
    public function enabled(){return $this->getObject('dbsysconfig','sysconfig')->getValue('PHOTO_REFRESH_ENABLED','photogallery','false')==='true';}
    public function gallery($id){return $this->getObject('photostore','photogallery')->one($id);}
    public function refresh($id)
    {
        if(!$this->canManage())throw new DomainException('forbidden');if(!$this->enabled())throw new DomainException('disabled');
        $row=$this->gallery($id);if(!$row)throw new DomainException('missing');
        // Public reads never call a provider. An unsuccessful explicit refresh preserves the previous snapshot.
        try{$items=$this->getObject('photoprovider','photogallery')->fetch($row['provider'],json_decode($row['settings'],true,512,JSON_THROW_ON_ERROR),$this->getObject('dbsysconfig','sysconfig')->getValue('PEXELS_API_KEY','photogallery',''));
            if(!$items)throw new RuntimeException('empty');
            $this->getObject('photostore','photogallery')->save(['id'=>$id,'items'=>json_encode($items,JSON_THROW_ON_ERROR),'fetched'=>gmdate('Y-m-d H:i:s')]);
        }catch(Throwable $error){throw new DomainException('failed');}
    }
}
